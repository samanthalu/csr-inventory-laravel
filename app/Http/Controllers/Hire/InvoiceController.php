<?php

namespace App\Http\Controllers\Hire;

use App\Http\Controllers\Controller;
use App\Mail\HireInvoiceMail;
use App\Models\Hire;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function index()
    {
        if (!Gate::allows('manage-invoices')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $invoices = Invoice::with('hire.staff')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($inv) => $this->format($inv));

        return response()->json(['data' => $invoices]);
    }

    public function generate(Request $request, $hireId)
    {
        if (!Gate::allows('manage-invoices')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $hire = Hire::with(['staff', 'items.product', 'items.invoiceItem'])->find($hireId);
        if (!$hire) {
            return response()->json(['message' => 'Hire not found'], 404);
        }

        // Only returned items that have not already been billed are candidates.
        $candidates = $hire->items->filter(fn($item) => $item->is_returned && !$item->invoiceItem);

        // Optionally bill an explicit subset of those candidates.
        $requestedIds = $request->input('item_ids');
        if (is_array($requestedIds) && count($requestedIds)) {
            $candidates = $candidates->whereIn('id', $requestedIds);
        }

        if ($candidates->isEmpty()) {
            return response()->json([
                'message' => 'Nothing to invoice: there are no returned, un-invoiced items on this hire.',
            ], 422);
        }

        // Guard: every billed item must have a hire rate, else it would bill as 0.
        $unrated = $candidates->filter(fn($item) => !($item->hire_rate_per_day > 0));
        if ($unrated->isNotEmpty()) {
            $names = $unrated->map(function ($item) {
                $name = $item->product->prod_name ?? ('Product #' . $item->product_id);
                $tag  = $item->product->prod_tag_number ?? null;
                return $tag ? "{$name} ({$tag})" : $name;
            })->values()->all();

            return response()->json([
                'message'       => 'Cannot generate invoice: the following items have no hire rate set: ' . implode(', ', $names) . '.',
                'unrated_items' => $names,
            ], 422);
        }

        // Build one line per item, billed to its ACTUAL return date.
        // Compare whole calendar days (Carbon returns a float otherwise).
        $hireStart = Carbon::parse($hire->hire_date)->startOfDay();
        $lines = $candidates->map(function ($item) use ($hire, $hireStart) {
            $end  = ($item->returned_at ? Carbon::parse($item->returned_at) : Carbon::parse($hire->hire_return_date))->startOfDay();
            $days = max(1, (int) $hireStart->diffInDays($end));
            return [
                'item'     => $item,
                'name'     => $item->product->prod_name ?? ('Product #' . $item->product_id),
                'tag'      => $item->product->prod_tag_number ?? '—',
                'qty'      => $item->quantity,
                'days'     => $days,
                'rate'     => (float) $item->hire_rate_per_day,
                'subtotal' => (float) $item->hire_rate_per_day * $item->quantity * $days,
                'returned' => $item->returned_at,
            ];
        })->values();

        $total = $lines->sum('subtotal');

        $invoiceNumber = 'INV-' . str_pad($hire->id, 4, '0', STR_PAD_LEFT) . '-' . now()->format('YmdHis');
        $relativePath  = 'invoices/' . $invoiceNumber . '.pdf';

        $pdf = Pdf::loadView('invoices.hire-invoice', [
            'hire'          => $hire,
            'invoiceNumber' => $invoiceNumber,
            'generatedAt'   => now()->format('d M Y, H:i'),
            'total'         => $total,
            'lines'         => $lines,
            'partial'       => $candidates->count() < $hire->items->count(),
        ])->setPaper('a4', 'portrait');

        $invoice = DB::transaction(function () use ($hire, $invoiceNumber, $relativePath, $total, $lines, $pdf) {
            Storage::disk('public')->put($relativePath, $pdf->output());

            $invoice = Invoice::create([
                'hire_id'        => $hire->id,
                'invoice_number' => $invoiceNumber,
                'file_path'      => $relativePath,
                'total_amount'   => $total,
            ]);

            foreach ($lines as $line) {
                InvoiceItem::create([
                    'invoice_id'   => $invoice->id,
                    'hire_item_id' => $line['item']->id,
                    'days'         => $line['days'],
                    'rate_per_day' => $line['rate'],
                    'subtotal'     => $line['subtotal'],
                ]);
            }

            return $invoice;
        });

        AuditLogger::log(
            'invoice',
            'generated',
            "Invoice {$invoiceNumber} generated for hire #{$hire->id} ({$lines->count()} item(s))",
            $invoice->id,
            null,
            $this->format($invoice->load('hire.staff'))
        );

        return response()->json([
            'message' => 'Invoice generated successfully',
            'data'    => $this->format($invoice->load('hire.staff')),
        ], 201);
    }

    public function download($id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice || !Storage::disk('public')->exists($invoice->file_path)) {
            return response()->json(['message' => 'Invoice file not found'], 404);
        }

        return response()->file(
            Storage::disk('public')->path($invoice->file_path),
            ['Content-Disposition' => 'attachment; filename="' . $invoice->invoice_number . '.pdf"']
        );
    }

    public function email($id)
    {
        if (!Gate::allows('manage-invoices')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $invoice = Invoice::with('hire.staff')->find($id);
        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        $staff = $invoice->hire?->staff;
        $email = $staff?->staff_email;
        if (!$email) {
            return response()->json(['message' => 'Cannot email invoice: the staff member has no email address on file.'], 422);
        }
        if (!Storage::disk('public')->exists($invoice->file_path)) {
            return response()->json(['message' => 'Invoice file not found'], 404);
        }

        $staffName = trim(($staff->staff_first_name ?? '') . ' ' . ($staff->staff_last_name ?? '')) ?: 'Staff Member';

        Mail::to($email)->queue(new HireInvoiceMail(
            staffName:     $staffName,
            invoiceNumber: $invoice->invoice_number,
            total:         (float) $invoice->total_amount,
            hireId:        $invoice->hire_id,
            filePath:      $invoice->file_path,
        ));

        $invoice->update(['emailed_at' => now(), 'emailed_to' => $email]);

        AuditLogger::log('invoice', 'emailed', "Invoice {$invoice->invoice_number} emailed to {$email}", $invoice->id);

        return response()->json([
            'message' => "Invoice queued for delivery to {$email}",
            'data'    => $this->format($invoice),
        ]);
    }

    public function destroy($id)
    {
        if (!Gate::allows('manage-invoices')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        Storage::disk('public')->delete($invoice->file_path);
        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted']);
    }

    private function format(Invoice $inv): array
    {
        return [
            'id'             => $inv->id,
            'hire_id'        => $inv->hire_id,
            'invoice_number' => $inv->invoice_number,
            'total_amount'   => $inv->total_amount,
            'download_url'   => route('invoices.download', $inv->id),
            'created_at'     => $inv->created_at?->format('d M Y, H:i'),
            'emailed_at'     => $inv->emailed_at?->format('d M Y, H:i'),
            'emailed_to'     => $inv->emailed_to,
            'hire'           => $inv->hire ? [
                'id'     => $inv->hire->id,
                'staff'  => $inv->hire->staff ? [
                    'name' => $inv->hire->staff->staff_first_name . ' ' . $inv->hire->staff->staff_last_name,
                ] : null,
                'hire_date'        => $inv->hire->hire_date,
                'hire_return_date' => $inv->hire->hire_return_date,
                'hire_status'      => $inv->hire->hire_status,
            ] : null,
        ];
    }
}
