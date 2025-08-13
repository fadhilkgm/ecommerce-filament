<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #f59e0b;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #92400e;
            margin-bottom: 10px;
        }
        .order-number {
            background-color: #fef3c7;
            color: #92400e;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            display: inline-block;
            margin: 15px 0;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-item {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            color: #374151;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-processing { background-color: #dbeafe; color: #1e40af; }
        .status-shipped { background-color: #e0e7ff; color: #3730a3; }
        .status-delivered { background-color: #d1fae5; color: #065f46; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        .order-items {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        .item-header {
            background-color: #f3f4f6;
            padding: 15px;
            font-weight: bold;
            color: #374151;
        }
        .order-item {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }
        .item-meta {
            font-size: 12px;
            color: #6b7280;
        }
        .item-price {
            font-weight: bold;
            color: #374151;
        }
        .total {
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            color: #92400e;
        }
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .order-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .item-price {
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">YAAL</div>
            <h1>Order Details</h1>
            <div class="order-number">Order #{{ $order->order_number }}</div>
        </div>

        <!-- Order Information -->
        <div class="section">
            <div class="section-title">Order Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Order Date</div>
                    <div class="info-value">{{ $order->created_at->format('F j, Y g:i A') }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Customer</div>
                    <div class="info-value">{{ $order->customer_name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Order Status</div>
                    <div class="info-value">
                        <span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Status</div>
                    <div class="info-value">
                        <span class="status-badge status-{{ $order->payment_status }}">{{ ucfirst($order->payment_status) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="section">
            <div class="section-title">Order Items</div>
            <div class="order-items">
                <div class="item-header">Items Ordered</div>
                @foreach($order->items as $item)
                    <div class="order-item">
                        <div class="item-details">
                            <div class="item-name">{{ $item->product_name }}</div>
                            <div class="item-meta">
                                @if($item->product_sku)
                                    SKU: {{ $item->product_sku }} • 
                                @endif
                                Quantity: {{ $item->quantity }} • 
                                Unit Price: ${{ number_format($item->unit_price, 2) }}
                            </div>
                            @if($item->variant_attributes)
                                <div class="item-meta">
                                    @foreach($item->variant_attributes as $attr)
                                        {{ $attr['name'] }}: {{ $attr['value'] }}@if(!$loop->last), @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="item-price">${{ number_format($item->total_price, 2) }}</div>
                    </div>
                @endforeach
            </div>

            <!-- Order Total -->
            <div class="total">
                Total: ${{ number_format($order->total_amount, 2) }}
            </div>
        </div>

        <!-- Shipping Information -->
        <div class="section">
            <div class="section-title">Shipping Information</div>
            <div class="info-item">
                <div class="info-label">Shipping Address</div>
                <div class="info-value">
                    {{ $order->shipping_address['full_name'] ?? $order->shipping_address['first_name'] . ' ' . $order->shipping_address['last_name'] }}<br>
                    @if(!empty($order->shipping_address['company']))
                        {{ $order->shipping_address['company'] }}<br>
                    @endif
                    {{ $order->shipping_address['address_line_1'] }}<br>
                    @if(!empty($order->shipping_address['address_line_2']))
                        {{ $order->shipping_address['address_line_2'] }}<br>
                    @endif
                    {{ $order->shipping_address['city'] }}, {{ $order->shipping_address['state'] }} {{ $order->shipping_address['postal_code'] }}<br>
                    {{ $order->shipping_address['country'] }}
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="section">
            <div class="section-title">Need Help?</div>
            <p>If you have any questions about your order, please contact us:</p>
            <ul>
                <li>Email: support@yaal.com</li>
                <li>Phone: +1 (555) 123-4567</li>
                <li>Order Number: {{ $order->order_number }}</li>
            </ul>
        </div>
    </div>
</body>
</html>