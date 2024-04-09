<!DOCTYPE html>
<html lang="en" dir="{{ $order->warehouse->is_rtl ?? false ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<title>Invoice #{{ $order->invoice_number }}</title>
<style>
body{font-family:'DejaVu Sans',sans-serif;font-size:12px;color:#333;margin:0;padding:20px}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}
th{background:#f5f5f5;font-weight:bold}
.header{display:flex;justify-content:space-between;margin-bottom:20px}
.totals{width:300px;margin-left:auto;margin-top:15px}
.totals td{border:none;padding:3px 8px}
.label{text-align:right;font-weight:bold}
.grand{font-size:14px;font-weight:bold}
</style>
</head>
<body>
<div class="header">
<div>
@if($company->logo)<img src="{{ public_path('uploads/companies/'.$company->logo) }}" height="50"><br>@endif
<strong>{{ $company->name }}</strong><br>
{{ $company->email }} | {{ $company->phone }}
</div>
<div style="text-align:right">
<h3 style="margin:0">INVOICE</h3>
<strong>#{{ $order->invoice_number }}</strong><br>
Date: {{ $order->order_date->format('d M Y') }}<br>
Status: {{ ucfirst($order->order_status) }}
</div>
</div>
@if($order->party)
<p><strong>Bill To:</strong> {{ $order->party->name }}<br>{{ $order->party->address }}</p>
@endif
<table>
<thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Price</th><th>Discount</th><th>Tax</th><th>Total</th></tr></thead>
<tbody>
@foreach($order->items as $i=>$item)
<tr>
<td>{{ $i+1 }}</td>
<td>{{ $item->product->name }}</td>
<td>{{ $item->quantity }}</td>
<td>{{ number_format($item->unit_price,2) }}</td>
<td>{{ number_format($item->discount,2) }}</td>
<td>{{ number_format($item->tax_amount,2) }}</td>
<td>{{ number_format($item->line_total,2) }}</td>
</tr>
@endforeach
</tbody>
</table>
<table class="totals">
<tr><td class="label">Subtotal:</td><td>{{ number_format($order->subtotal,2) }}</td></tr>
<tr><td class="label">Discount:</td><td>-{{ number_format($order->discount,2) }}</td></tr>
<tr><td class="label">Tax:</td><td>{{ number_format($order->tax_amount,2) }}</td></tr>
<tr><td class="label">Shipping:</td><td>{{ number_format($order->shipping,2) }}</td></tr>
<tr class="grand"><td class="label">Grand Total:</td><td>{{ number_format($order->grand_total,2) }}</td></tr>
<tr><td class="label">Paid:</td><td>{{ number_format($order->grand_total-$order->due_amount,2) }}</td></tr>
<tr><td class="label">Due:</td><td>{{ number_format($order->due_amount,2) }}</td></tr>
</table>
</body>
</html>
