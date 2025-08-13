<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class ApprovePayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:approve-payment {order-number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Approve payment for a manual payment order';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderNumber = $this->argument('order-number');
        
        $order = Order::where('order_number', $orderNumber)->first();
        
        if (!$order) {
            $this->error("Order {$orderNumber} not found.");
            return 1;
        }

        if ($order->payment_method !== 'manual_payment') {
            $this->error("Order {$orderNumber} is not a manual payment order.");
            return 1;
        }

        if ($order->payment_status === 'paid') {
            $this->info("Order {$orderNumber} is already approved.");
            return 0;
        }

        $this->info("Order Details:");
        $this->info("- Order Number: {$order->order_number}");
        $this->info("- Customer: {$order->customer_name} ({$order->customer_email})");
        $this->info("- Total Amount: \${$order->total_amount}");
        $this->info("- Payment Reference: {$order->payment_reference}");
        $this->info("- Current Status: {$order->payment_status}");

        if ($this->confirm('Do you want to approve this payment?')) {
            $order->update([
                'payment_status' => 'paid',
                'status' => $order->status === 'pending' ? 'processing' : $order->status
            ]);

            $this->info('✅ Payment approved successfully!');
            $this->info('📧 Payment approval email will be sent automatically.');
        } else {
            $this->info('Payment approval cancelled.');
        }

        return 0;
    }
}