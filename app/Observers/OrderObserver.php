<?php

namespace App\Observers;

use App\Models\Order;
use App\Mail\PaymentApproved;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Check if payment status was changed to 'paid'
        if ($order->isDirty('payment_status') && $order->payment_status === 'paid') {
            // Only send email for manual payments that were just approved
            if ($order->payment_method === 'manual_payment') {
                try {
                    // Load order with items for email
                    $order->load('items');
                    Mail::to($order->customer_email)->send(new PaymentApproved($order));
                } catch (\Exception $emailError) {
                    // Log email error but don't fail the update
                    \Log::error('Failed to send payment approval email', [
                        'order_id' => $order->id,
                        'customer_email' => $order->customer_email,
                        'error' => $emailError->getMessage()
                    ]);
                }
            }
        }
    }
}