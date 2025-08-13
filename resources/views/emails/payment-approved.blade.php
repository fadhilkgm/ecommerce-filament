<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Approved</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }

        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #10b981;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #92400e;
            margin-bottom: 10px;
        }

        .success-icon {
            font-size: 40px;
            color: #10b981;
            margin-bottom: 10px;
        }

        .order-number {
            background-color: #d1fae5;
            color: #065f46;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            display: inline-block;
            margin: 15px 0;
        }

        .section {
            margin-bottom: 25px;
        }

        .success-message {
            background-color: #d1fae5;
            border: 1px solid #a7f3d0;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .success-message h3 {
            margin: 0 0 10px 0;
            color: #065f46;
        }

        .total {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #10b981;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">YAAL</div>
            <div class="success-icon">✅</div>
            <h2>Payment Approved!</h2>
            <div class="order-number">Order #{{ $order->order_number }}</div>
        </div>

        <!-- Success Message -->
        <div class="success-message">
            <h3>🎉 Great News!</h3>
            <p>Your payment has been verified and approved. Your order is now being processed!</p>
        </div>

        <!-- Greeting -->
        <div class="section">
            <p>Hi {{ $customer['name'] }},</p>
            <p>Your payment has been successfully verified and your order is moving forward.</p>

            @if($order->payment_reference)
            <p><strong>Payment Reference:</strong> {{ $order->payment_reference }}</p>
            @endif

            <div class="total">
                Total: ₹ {{ number_format($order->total_amount, 2) }} ✅ PAID
            </div>
        </div>

        <!-- Next Steps -->
        <div class="section">
            <p><strong>What's Next:</strong></p>
            <ul>
                <li>Your order is being prepared for shipment</li>
                <li>You'll receive tracking info within 1-2 business days</li>
                <li>Estimated delivery: 3-7 business days</li>
            </ul>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Questions? Email us at support@yaalstore.in</p>
            <p>&copy; {{ date('Y') }} YAAL. All rights reserved.</p>
        </div>
    </div>
</body>

</html>