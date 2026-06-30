<?php

namespace App\Http\Controllers\Hire;

use App\Http\Controllers\Controller;
use App\Mail\HireInvoiceMail;
use App\Models\Hire;
use App\Models\Invoice;
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
    private function calcTotal(Hire $hire): float
    {
        $days = max(1, Carbon::parse($hire->hire_date)->diffInDays(Carbon::parse($hire->hire_return_date)));
        return $hire->items->sum(fn($item) => ($item->hire_rate_per_day ?? 0) * $item->quantity * $days);
    }

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
        $hire = Hire::with(['staff', 'items.product'])->find($hireId);
        if (!$hire) {
            return response()->json(['message' => 'Hire not found'], 404);
        }

        // Guard: the hire must have at least one item to bill.
        if ($hire->items->isEmpty()) {
            return response()->json(['message' => 'Cannot generate invoice: this hire has no items.'], 422);
        }

        // Guard: every item must have a hire rate, otherwise it would silently bill as 0.
        $unrated = $hire->items->filter(fn($item) => !($item->hire_rate_per_day > 0));
        if ($unrated->isNotEmpty()) {
            $names = $unrated->map(function ($item) {
                $name = $item->product->prod_name ?? ('Product #' . $item->product_id);
                $tag  = $item->product->prod_tag ?? null;
                return $tag ? "{$name} ({$tag})" : $name;
            })->values()->all();

            return response()->json([
                'message'        => 'Cannot generate invoice: the following items have no hire rate set: ' . implode(', ', $names) . '.',
                'unrated_items'  => $names,
            ], 422);
        }

        // Duplicate handling: replace existing invoice(s) only on explicit confirmation.
        $existing = Invoice::where('hire_id', $hire->id)->get();
        if ($existing->isNotEmpty() && !$request->boolean('replace')) {
            return response()->json([
                'message'               => 'An invoice already exists for this hire. Generating a new one will replace it.',
                'requires_confirmation' => true,
            ], 409);
        }

        $invoiceNumber = 'INV-' . str_pad($hire->id, 4, '0', STR_PAD_LEFT) . '-' . now()->format('YmdHis');
        $total         = $this->calcTotal($hire);

        $pdf = Pdf::loadView('invoices.hire-invoice', [
            'hire'          => $hire,
            'invoiceNumber' => $invoiceNumber,
            'generatedAt'   => now()->format('d M Y, H:i'),
            'total'         => $total,
        ])->setPaper('a4', 'portrait');

        $relativePath = 'invoices/' . $invoiceNumber . '.pdf';

        $invoice = DB::transaction(function () use ($existing, $hire, $invoiceNumber, $relativePath, $total, $pdf) {
            // Remove superseded invoices (DB rows first; files after commit to avoid orphan rows).
            foreach ($existing as $old) {
                $old->delete();
            }

            Storage::disk('public')->put($relativePath, $pdf->output());

            return Invoice::create([
                'hire_id'        => $hire->id,
                'invoice_number' => $invoiceNumber,
                'file_path'      => $relativePath,
                'total_amount'   => $total,
            ]);
        });

        // Files of replaced invoices are cleaned up only once the new row is committed.
        foreach ($existing as $old) {
            if ($old->file_path) {
                Storage::disk('public')->delete($old->file_path);
            }
        }

        $action = $existing->isNotEmpty() ? 'regenerated' : 'generated';
        AuditLogger::log(
            'invoice',
            $action,
            "Invoice {$invoiceNumber} {$action} for hire #{$hire->id}",
            $invoice->id,
            null,
            $this->format($invoice->load('hire.staff'))
        );

        return response()->json([
            'message' => $existing->isNotEmpty() ? 'Invoice regenerated successfully' : 'Invoice generated successfully',
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
