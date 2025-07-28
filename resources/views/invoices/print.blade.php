<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 14px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
        }
        
        /* Header Table */
        .header-table {
            width: 100%;
            background: #4a90e2;
            color: white;
        }
        
        .header-table td {
            padding: 20px;
            text-align: center;
        }
        
        .header-table h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        
        .header-table .invoice-number {
            margin: 5px 0 0 0;
            font-size: 16px;
        }
        
        /* Info Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .info-table td {
            padding: 15px;
            vertical-align: top;
            border: 1px solid #ddd;
        }
        
        .info-table .info-header {
            background: #f5f5f5;
            font-weight: bold;
            color: #4a90e2;
            border-bottom: 2px solid #4a90e2;
        }
        
        .info-table p {
            margin: 5px 0;
        }
        
        .info-table strong {
            color: #333;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .items-table th {
            background: #4a90e2;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #4a90e2;
        }
        
        .items-table td {
            padding: 10px 8px;
            border: 1px solid #ddd;
        }
        
        .items-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .rupee {
            color: #27ae60;
            font-weight: bold;
        }
        
        /* Summary Table */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .summary-table td {
            padding: 8px 15px;
            border: 1px solid #ddd;
        }
        
        .summary-table .summary-label {
            background: #f5f5f5;
            font-weight: bold;
            text-align: right;
            width: 70%;
        }
        
        .summary-table .summary-value {
            text-align: right;
            font-weight: bold;
            width: 30%;
        }
        
        .summary-table .total-row {
            background: #4a90e2;
            color: white;
            font-size: 16px;
        }
        
        .discount-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .payment-method {
            background: #3498db;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin: 2px;
            display: inline-block;
        }
        
        .status-paid {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-pending {
            color: #e74c3c;
            font-weight: bold;
        }
        
        /* Footer Table */
        .footer-table {
            width: 100%;
            background: #333;
            color: white;
            margin-top: 20px;
        }
        
        .footer-table td {
            padding: 15px;
            text-align: center;
        }
        
        @media print {
            body { padding: 10px; }
            .invoice-container { border: none; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td>
                    <h1>INVOICE</h1>
                    <p class="invoice-number"># {{ $invoice->invoice_number }}</p>
                </td>
            </tr>
        </table>

        <!-- Invoice & Customer Info -->
        <table class="info-table">
            <tr>
                <td class="info-header">Invoice Details</td>
                <td class="info-header">Bill To</td>
            </tr>
            <tr>
                <td style="width: 50%;">
                    <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
                    <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($invoice->date)->format('d M, Y') }}</p>
                    <p><strong>Status:</strong> 
                        <span class="{{ $invoice->status ? 'status-paid' : 'status-pending' }}">
                            {{ $invoice->status ? 'PAID' : 'PENDING' }}
                        </span>
                    </p>
                    @if($invoice->payment_method)
                        <p><strong>Payment Method:</strong></p>
                        <div style="margin-top: 5px;">
                            @if(is_array($invoice->payment_method))
                                @foreach($invoice->payment_method as $method)
                                    <span class="payment-method">{{ strtoupper($method) }}</span>
                                @endforeach
                            @else
                                <span class="payment-method">{{ strtoupper($invoice->payment_method) }}</span>
                            @endif
                        </div>
                    @endif
                </td>
                <td style="width: 50%;">
                    @if($invoice->customer_name)
                        <p><strong>{{ $invoice->customer_name }}</strong></p>
                    @else
                        <p><strong>Walk-in Customer</strong></p>
                    @endif
                    @if($invoice->customer_phone)
                        <p><strong>Phone:</strong> {{ $invoice->customer_phone }}</p>
                    @endif
                    @if($invoice->customer_email)
                        <p><strong>Email:</strong> {{ $invoice->customer_email }}</p>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">Product</th>
                    <th style="width: 20%;">Variant</th>
                    <th style="width: 10%;" class="text-center">Qty</th>
                    <th style="width: 12%;" class="text-right">Unit Price</th>
                    <th style="width: 13%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $item->product->name ?? 'N/A' }}</strong>
                            @if($item->product->category)
                                <br><small style="color: #666;">{{ $item->product->category->name }}</small>
                            @endif
                        </td>
                        <td>
                            @if($item->variant && $item->variant->attributes->count() > 0)
                                <small>{{ $item->variant->attributes->pluck('value')->join(' - ') }}</small>
                            @else
                                <small>Standard</small>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">
                            <span class="rupee"> {{ number_format($item->unit_price, 2) }}</span>
                        </td>
                        <td class="text-right">
                            <span class="rupee"> {{ number_format($item->total, 2) }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Table -->
        <table class="summary-table">
            @php
                $subtotal = $invoice->items->sum('total');
                $discountAmount = $invoice->total_discount ? ($subtotal * $invoice->total_discount / 100) : 0;
            @endphp
            
            <tr>
                <td class="summary-label">Subtotal:</td>
                <td class="summary-value">
                    <span class="rupee"> {{ number_format($subtotal, 2) }}</span>
                </td>
            </tr>
            
            @if($invoice->total_discount && $invoice->total_discount > 0)
                <tr>
                    <td class="summary-label">
                        Discount <span class="discount-badge">{{ $invoice->total_discount }}%</span>:
                    </td>
                    <td class="summary-value">
                        <span class="rupee" style="color: #e74c3c;">- {{ number_format($discountAmount, 2) }}</span>
                    </td>
                </tr>
            @endif
            
            <tr class="total-row">
                <td class="summary-label" style="color: black;">TOTAL AMOUNT:</td>
                <td class="summary-value">
                    <span style="font-size: 18px;"> {{ number_format($invoice->total_amount, 2) }}</span>
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <table class="footer-table">
            <tr>
                <td>
                    <p style="font-size: 16px; font-weight: bold; margin-bottom: 5px;">Thank you for your business!</p>
                    <p style="font-size: 12px;">Generated on {{ now()->format('d M, Y \a\t H:i:s') }}</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>