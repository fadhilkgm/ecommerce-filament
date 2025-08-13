<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Label - Order {{ $order->order_number }}</title>
    <style>
        @page {
            size: 4in 6in;
            margin: 0.2in;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: white;
            color: #333;
            font-size: 12px;
            line-height: 1.3;
        }

        .sticker-container {
            width: 100%;
            height: 100%;
            border: 2px solid #333;
            padding: 15px;
            box-sizing: border-box;
            position: relative;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #f59e0b;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #92400e;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 10px;
            color: #666;
        }

        .order-info {
            background-color: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #f59e0b;
        }

        .order-number {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
        }

        .order-date {
            font-size: 10px;
            color: #666;
        }

        .addresses {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .address-section {
            width: 48%;
        }

        .address-title {
            font-size: 11px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            text-transform: uppercase;
            border-bottom: 1px solid #ddd;
            padding-bottom: 2px;
        }

        .address-content {
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }

        .address-name {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .qr-section {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }

        .qr-code {
            margin-bottom: 8px;
        }

        .qr-text {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
        }

        .tracking-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 10px;
            text-align: center;
        }

        .tracking-title {
            font-size: 10px;
            font-weight: bold;
            color: #856404;
            margin-bottom: 3px;
        }

        .tracking-number {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            font-family: 'Courier New', monospace;
        }

        .footer {
            position: absolute;
            bottom: 15px;
            left: 15px;
            right: 15px;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }

        .items-summary {
            background-color: #f8f9fa;
            padding: 6px;
            border-radius: 3px;
            margin-bottom: 10px;
            font-size: 10px;
        }

        .items-title {
            font-weight: bold;
            margin-bottom: 3px;
            color: #333;
        }

        .item-count {
            color: #666;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .sticker-container {
                border: 2px solid #333 !important;
            }
        }
    </style>
</head>

<body>
    <div class="sticker-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">{{ $companyInfo['company_name'] ?? 'YAAL' }}</div>
            <div class="company-info">Premium Jewelry & Accessories <br>
                https://yaalstore.in
            </div>

        </div>

        <!-- Order Information -->
        <div class="order-info">
            <div class="order-number">Order #{{ $order->order_number }}</div>
            <div class="order-date">{{ $order->created_at->format('M j, Y g:i A') }}</div>
        </div>

        <!-- Items Summary -->
        <div class="items-summary">
            <div class="items-title">Package Contents:</div>
            <div class="item-count">{{ $order->items->count() }} item(s)
            </div>
        </div>

        <!-- Tracking Information -->
        @if($order->tracking_number ?? false)
        <div class="tracking-info">
            <div class="tracking-title">Tracking Number</div>
            <div class="tracking-number">{{ $order->tracking_number }}</div>
        </div>
        @endif

        <!-- Addresses -->
        <div class="addresses">
            <!-- Shipping Address -->
            <div class="address-section">
                <div class="address-title">Ship To:</div>
                <div class="address-content">
                    <div class="address-name">
                        {{ $order->shipping_address['full_name'] ?? $order->shipping_address['first_name'] . ' ' .
                        $order->shipping_address['last_name'] }}
                    </div>
                    @if(!empty($order->shipping_address['company']))
                    <div>{{ $order->shipping_address['company'] }}</div>
                    @endif
                    <div>{{ $order->shipping_address['address_line_1'] }}</div>
                    @if(!empty($order->shipping_address['address_line_2']))
                    <div>{{ $order->shipping_address['address_line_2'] }}</div>
                    @endif
                    <div>
                        {{ $order->shipping_address['city'] }}, {{ $order->shipping_address['state'] }} {{
                        $order->shipping_address['postal_code'] }}
                    </div>
                    <div>{{ $order->shipping_address['country'] }}</div>
                    @if(!empty($order->shipping_address['phone']))
                    <div style="margin-top: 3px; font-size: 10px;">
                        📞 {{ $order->shipping_address['phone'] }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- Return Address -->
            <div class="address-section">
                <div class="address-title">From:</div>
                <div class="address-content">
                    <div class="address-name">{{ $companyInfo['company_name'] ?? 'YAAL Jewelry' }}</div>
                    @if(isset($companyInfo['company_address']))
                    @foreach(explode("\n", $companyInfo['company_address']) as $addressLine)
                    @if(trim($addressLine))
                    <div>{{ trim($addressLine) }}</div>
                    @endif
                    @endforeach
                    @else
                    <div>123 Business Street</div>
                    <div>Suite 100</div>
                    <div>New York, NY 10001</div>
                    <div>United States</div>
                    @endif
                    <div style="margin-top: 3px; font-size: 10px;">
                        📞 {{ $companyInfo['company_phone'] ?? '+1 (555) 123-4567' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code Section -->
        <div class="qr-section">
            <div class="qr-code">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode($qrCodeUrl) }}"
                    alt="Order QR Code" style="width: 80px; height: 80px; border: 1px solid #ddd;">
            </div>
            <div class="qr-text">Scan for order details</div>
        </div>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function () {
            setTimeout(function () {
                window.print();
            }, 500);
        };
    </script>
</body>

</html>