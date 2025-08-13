<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Mail\OrderConfirmation;
use App\Mail\PaymentApproved;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {type=confirmation} {--order-id=} {--email=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $orderId = $this->option('order-id');
        $email = $this->option('email') ?? 'test@example.com';

        if (!$orderId) {
            // Get the latest order for testing
            $order = Order::with('items')->latest()->first();
            
            if (!$order) {
                $this->error('No orders found. Please create an order first or specify --order-id');
                return 1;
            }
        } else {
            $order = Order::with('items')->find($orderId);
            
            if (!$order) {
                $this->error("Order with ID {$orderId} not found.");
                return 1;
            }
        }

        $this->info("Testing {$type} email for Order #{$order->order_number}");
        $this->info("Sending to: {$email}");

        try {
            switch ($type) {
                case 'confirmation':
                    Mail::to($email)->send(new OrderConfirmation($order));
                    $this->info('✅ Order confirmation email sent successfully!');
                    break;
                    
                case 'payment-approved':
                    Mail::to($email)->send(new PaymentApproved($order));
                    $this->info('✅ Payment approved email sent successfully!');
                    break;
                    
                default:
                    $this->error("Unknown email type: {$type}");
                    $this->info('Available types: confirmation, payment-approved');
                    return 1;
            }

            $this->info("Email sent to: {$email}");
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to send email: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}