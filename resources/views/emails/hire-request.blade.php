<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: Arial, sans-serif; color: #334155; margin: 0; padding: 0; background: #f1f5f9; }
  .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
  .header { background: #1e40af; padding: 28px 32px; }
  .header h1 { color: #fff; margin: 0; font-size: 20px; }
  .header p { color: #bfdbfe; margin: 4px 0 0; font-size: 13px; }
  .body { padding: 28px 32px; }
  .badge { display: inline-block; background: #fef9c3; color: #854d0e; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 99px; margin-bottom: 18px; }
  .field { margin-bottom: 16px; }
  .field label { display: block; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 3px; }
  .field p { margin: 0; font-size: 14px; color: #1e293b; }
  .items-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  .items-table th { text-align: left; font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; padding: 6px 10px; border-bottom: 1px solid #e2e8f0; }
  .items-table td { padding: 8px 10px; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
  .divider { border: none; border-top: 1px solid #e2e8f0; margin: 20px 0; }
  .footer { padding: 16px 32px; background: #f8fafc; font-size: 12px; color: #94a3b8; }
  .cta { display: inline-block; margin-top: 20px; background: #1e40af; color: #fff; padding: 10px 22px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>New Hire Request</h1>
    <p>CSR Inventory Management System &mdash; {{ config('app.name') }}</p>
  </div>
  <div class="body">
    <span class="badge">&#9679; Pending Review</span>

    <div class="field">
      <label>Submitted by</label>
      <p>{{ $hireRequest->requestedBy?->name ?? 'Unknown' }}</p>
    </div>

    @if($hireRequest->staff)
    <div class="field">
      <label>Hire for staff member</label>
      <p>{{ $hireRequest->staff->staff_first_name }} {{ $hireRequest->staff->staff_last_name }} &mdash; {{ $hireRequest->staff->staff_position }}</p>
    </div>
    @endif

    <div class="field">
      <label>Purpose</label>
      <p>{{ $hireRequest->purpose }}</p>
    </div>

    <div class="field">
      <label>Requested Period</label>
      <p>{{ \Carbon\Carbon::parse($hireRequest->requested_start_date)->format('d M Y') }} &ndash; {{ \Carbon\Carbon::parse($hireRequest->requested_end_date)->format('d M Y') }}</p>
    </div>

    @if($hireRequest->notes)
    <div class="field">
      <label>Additional Notes</label>
      <p>{{ $hireRequest->notes }}</p>
    </div>
    @endif

    <hr class="divider">

    <div class="field">
      <label>Items Requested</label>
      <table class="items-table">
        <thead>
          <tr>
            <th>Category</th>
            <th>Quantity</th>
          </tr>
        </thead>
        <tbody>
          @foreach($hireRequest->items as $item)
          <tr>
            <td>{{ $item->category?->cat_name ?? 'N/A' }}</td>
            <td>{{ $item->quantity }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <a href="{{ config('app.url') }}" class="cta">Review in System</a>
  </div>
  <div class="footer">
    This is an automated notification from the CSR Inventory System. Please do not reply to this email.
  </div>
</div>
</body>
</html>
