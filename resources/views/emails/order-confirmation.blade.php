<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            line-height: 1.6;
            color: #2c3e50;
            max-width: 650px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f7f6;
        }

        .email-container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #3498db;
            padding-bottom: 25px;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 32px;
            font-weight: 700;
            color: #3da004;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .header h2 {
            margin: 0;
            font-size: 24px;
            color: #34495e;
        }

        .order-number {
            background-color: #e8f6fd;
            color: #3da004;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            margin: 20px 0;
            font-size: 16px;
        }

        .section {
            margin-bottom: 30px;
        }

        h3 {
            font-size: 20px;
            color: #34495e;
            border-bottom: 1px solid #ecf0f1;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .order-item {
            padding: 15px 0;
            border-bottom: 1px solid #ecf0f1;
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
            font-weight: 600;
            color: #34495e;
            font-size: 16px;
        }

        .item-meta {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 4px;
        }

        .item-price {
            font-weight: 700;
            color: #34495e;
            font-size: 16px;
            text-align: right;
            min-width: 80px;
        }

        .total {
            background-color: #f8f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            border-left: 4px solid #3498db;
        }

        .total span {
            color: #3498db;
        }

        .payment-status {
            background-color: #f4f7f6;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            line-height: 1.5;
        }

        .payment-status.paid {
            background-color: #e6f7ec;
            border: 1px solid #a8e6cf;
        }

        .payment-status.unpaid {
            background-color: #fcf8e3;
            border: 1px solid #f9e79f;
        }

        .payment-status strong {
            font-size: 18px;
        }

        .payment-status small {
            font-size: 14px;
            color: #555;
            display: block;
            margin-top: 5px;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid #ecf0f1;
            color: #95a5a6;
            font-size: 14px;
        }

        .footer p {
            margin: 5px 0;
        }

        @media (max-width: 600px) {
            .email-container {
                padding: 20px;
            }

            .order-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .item-details {
                margin-bottom: 8px;
            }

            .item-price {
                text-align: left;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">YAAL</div>
            <h2>Order Confirmation</h2>
            <div class="order-number">Order #{{ $order->order_number }}</div>
        </div>

        <div class="section">
            <p>Hi {{ $customer['name'] }},</p>
            <p>Thank you for your recent purchase! We've received your order and are getting it ready for you.</p>
        </div>

        <div class="payment-status {{ $isManualPayment ? 'unpaid' : 'paid' }}">
            @if($isManualPayment)
            <strong>⏳ Payment Verification Required</strong>
            @if($order->payment_reference)
            <small>Reference: {{ $order->payment_reference }}</small>
            @endif
            <small>We're currently verifying your payment. We'll send you an update as soon as it's confirmed, typically
                within 24-48 hours.</small>
            @else
            <strong>✅ Payment Confirmed</strong>
            <small>Your payment has been successfully processed. We're now preparing your order for shipment.</small>
            @endif
        </div>

        <div class="section">
            <h3>Your Items</h3>
            @foreach($order->items as $item)
            <div class="order-item">
                <div class="item-details">
                    <div class="item-name">{{ $item->product_name }}</div>
                    <div class="item-meta">Quantity: {{ $item->quantity }}</div>
                </div>
                <div class="item-price">${{ number_format($item->total_price, 2) }}</div>
            </div>
            @endforeach

            <div class="total">
                <span>Total:</span>
                <span>₹ {{ number_format($order->total_amount, 2) }}</span>
            </div>
        </div>

        <div class="section">
            @if($isManualPayment)
            <p><strong>What's Next?</strong> We will notify you via email once your payment is successfully verified. We
                appreciate your patience!</p>
            @else
            <p><strong>What's Next?</strong> Your order is being packed and will be shipped shortly. You'll receive a
                separate email with tracking information as soon as it's on its way.</p>
            @endif
        </div>

        <div class="footer">
            <p>Have questions? Please don't hesitate to reach out to our support team at <a
                    href="mailto:support@yaalstore.in" style="color: #3498db; text-decoration: none;">support@yaal.com</a>.
            </p>
            <p>&copy; {{ date('Y') }} YAAL. All rights reserved.</p>
        </div>
    </div>
</body>

</html>