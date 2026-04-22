<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #1e293b; background: #fff; }

  .page { padding: 48px 52px; }

  /* Header */
  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 36px; }
  .org-name { font-size: 22px; font-weight: 700; color: #1e3a5f; letter-spacing: -0.5px; }
  .org-sub  { font-size: 11px; color: #64748b; margin-top: 3px; }
  .invoice-meta { text-align: right; }
  .invoice-title { font-size: 28px; font-weight: 800; color: #1e3a5f; letter-spacing: 2px; }
  .invoice-num   { font-size: 11px; color: #64748b; margin-top: 4px; }
  .invoice-date  { font-size: 11px; color: #64748b; }

  hr.divider { border: none; border-top: 2px solid #e2e8f0; margin: 24px 0; }

  /* Bill To / Hire Details */
  .two-col { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
  .two-col td { width: 50%; vertical-align: top; padding-right: 24px; }
  .section-label { font-size: 10px; font-weight: 700; text-transform: uppercase;
                   letter-spacing: 1px; color: #94a3b8; margin-bottom: 8px; }
  .detail-name  { font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
  .detail-line  { font-size: 12px; color: #475569; margin-bottom: 2px; }

  .status-badge { display: inline-block; padding: 2px 10px; border-radius: 20px;
                  font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
  .status-active   { background: #dbeafe; color: #1d4ed8; }
  .status-returned { background: #dcfce7; color: #15803d; }
  .status-overdue  { background: #fee2e2; color: #b91c1c; }

  /* Table */
  .table-wrap { margin-bottom: 24px; }
  table { width: 100%; border-collapse: collapse; }
  thead tr { background: #1e3a5f; color: #fff; }
  thead th { padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
  thead th.right { text-align: right; }
  tbody tr:nth-child(even) { background: #f8fafc; }
  tbody tr { border-bottom: 1px solid #e2e8f0; }
  tbody td { padding: 10px 12px; font-size: 12px; vertical-align: top; }
  tbody td.right { text-align: right; }
  tbody td.muted { color: #64748b; font-size: 11px; }
  .returned-badge { color: #16a34a; font-size: 10px; font-weight: 600; }

  /* Totals */
  .totals-wrap { width: 100%; margin-bottom: 32px; }
  .totals-box { width: 260px; margin-left: auto; border-collapse: collapse; }
  .totals-row td { padding: 5px 4px; font-size: 12px; color: #475569;
                   border-bottom: 1px solid #f1f5f9; }
  .totals-row td.right { text-align: right; }
  .totals-row.grand td { border-bottom: none; border-top: 2px solid #1e3a5f;
                         padding-top: 10px; font-size: 15px; font-weight: 700; color: #1e3a5f; }

  /* Footer */
  .footer { border-top: 1px solid #e2e8f0; padding-top: 16px;
            font-size: 10px; color: #94a3b8; text-align: center; }
  .notes-box { background: #f8fafc; border-left: 3px solid #cbd5e1;
               padding: 10px 14px; margin-bottom: 20px; font-size: 12px; color: #475569; }
  .notes-label { font-weight: 700; color: #1e293b; }
</style>
</head>
<body>
<div class="page">

  <!-- Header -->
  <div class="header">
    <div>
      <div class="org-name">CSR Inventory System</div>
      <div class="org-sub">Equipment Hire Management</div>
    </div>
    <div class="invoice-meta">
      <div class="invoice-title">INVOICE</div>
      <div class="invoice-num">{{ $invoiceNumber }}</div>
      <div class="invoice-date">Generated: {{ $generatedAt }}</div>
    </div>
  </div>

  <hr class="divider">

  <!-- Bill To + Hire Details -->
  <table class="two-col">
    <tr>
      <td>
        <div class="section-label">Bill To</div>
        <div class="detail-name">
          {{ $hire->staff->staff_first_name ?? '' }} {{ $hire->staff->staff_last_name ?? 'Unknown Staff' }}
        </div>
        @if($hire->staff)
          <div class="detail-line">{{ $hire->staff->staff_position ?? '' }}</div>
          <div class="detail-line">{{ $hire->staff->staff_email ?? '' }}</div>
          <div class="detail-line">{{ $hire->staff->staff_phone ?? '' }}</div>
        @endif
      </td>
      <td>
        <div class="section-label">Hire Details</div>
        <div class="detail-line"><strong>Hire #:</strong> {{ $hire->id }}</div>
        <div class="detail-line"><strong>Status:</strong>
          <span class="status-badge status-{{ $hire->hire_status ?? 'active' }}">
            {{ ucfirst($hire->hire_status ?? 'active') }}
          </span>
        </div>
        @if($hire->hire_purpose)
          <div class="detail-line"><strong>Purpose:</strong> {{ $hire->hire_purpose }}</div>
        @endif
        <div class="detail-line"><strong>Hire Date:</strong> {{ \Carbon\Carbon::parse($hire->hire_date)->format('d M Y') }}</div>
        <div class="detail-line"><strong>Return By:</strong> {{ \Carbon\Carbon::parse($hire->hire_return_date)->format('d M Y') }}</div>
      </td>
    </tr>
  </table>

  <!-- Products Table -->
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Product</th>
          <th>Tag</th>
          <th class="right">Qty</th>
          <th class="right">Days</th>
          <th class="right">Rate / Day</th>
          <th class="right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        @foreach($hire->items as $i => $item)
          @php
            $days = max(1, \Carbon\Carbon::parse($hire->hire_date)
                           ->diffInDays(\Carbon\Carbon::parse($hire->hire_return_date)));
            $rate     = $item->hire_rate_per_day ?? 0;
            $subtotal = $rate * $item->quantity * $days;
          @endphp
          <tr>
            <td class="muted">{{ $i + 1 }}</td>
            <td>
              {{ $item->product->prod_name ?? 'Product #'.$item->product_id }}
              @if($item->is_returned)
                <br><span class="returned-badge">✓ Returned</span>
              @endif
            </td>
            <td class="muted">{{ $item->product->prod_tag_number ?? '—' }}</td>
            <td class="right">{{ $item->quantity }}</td>
            <td class="right">{{ $days }}</td>
            <td class="right">{{ $rate > 0 ? 'MWK '.number_format($rate, 2) : '—' }}</td>
            <td class="right">{{ $subtotal > 0 ? 'MWK '.number_format($subtotal, 2) : '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <!-- Totals -->
  <div class="totals-wrap">
    <table class="totals-box">
      <tr class="totals-row">
        <td>Items</td>
        <td class="right">{{ $hire->items->count() }}</td>
      </tr>
      <tr class="totals-row grand">
        <td>Total</td>
        <td class="right">MWK {{ number_format($total, 2) }}</td>
      </tr>
    </table>
  </div>

  @if($hire->hire_notes)
  <div class="notes-box">
    <span class="notes-label">Notes: </span>{{ $hire->hire_notes }}
  </div>
  @endif

  <!-- Footer -->
  <div class="footer">
    This invoice was automatically generated by CSR Inventory System &bull;
    {{ $generatedAt }} &bull; {{ $invoiceNumber }}
  </div>

</div>
</body>
</html>
