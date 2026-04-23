<?php

namespace App\Http\Controllers\Hire;

use App\Http\Controllers\Controller;
use App\Models\Hire;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
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

    public function generate($hireId)
    {
        if (!Gate::allows('manage-invoices')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $hire = Hire::with(['staff', 'items.product'])->find($hireId);
        if (!$hire) {
            return response()->json(['message' => 'Hire not found'], 404);
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
        Storage::disk('public')->put($relativePath, $pdf->output());

        $invoice = Invoice::create([
            'hire_id'        => $hire->id,
            'invoice_number' => $invoiceNumber,
            'file_path'      => $relativePath,
            'total_amount'   => $total,
        ]);

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
