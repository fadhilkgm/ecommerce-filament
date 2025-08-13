<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StickerController extends Controller
{
    public function printSticker(Order $order)
    {
        // Generate QR code URL that links to frontend order details
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $qrCodeUrl = $frontendUrl . '/order-confirmation/' . $order->order_number;
        
        // Get company information from settings
        $companyInfo = [
            'company_name' => CompanySetting::getValue('company_name', 'YAAL Jewelry', $order->shop_id),
            'company_address' => CompanySetting::getValue('company_address', "123 Business Street\nSuite 100\nNew York, NY 10001\nUnited States", $order->shop_id),
            'company_phone' => CompanySetting::getValue('company_phone', '+1 (555) 123-4567', $order->shop_id),
            'company_email' => CompanySetting::getValue('company_email', 'support@yaal.com', $order->shop_id),
        ];
        
        return view('stickers.shipping-label', [
            'order' => $order,
            'qrCodeUrl' => $qrCodeUrl,
            'companyInfo' => $companyInfo,
        ]);
    }

    public function bulkPrintStickers(Request $request)
    {
        $orderIds = explode(',', $request->get('orders', ''));
        $orders = Order::whereIn('id', $orderIds)->with('items')->get();
        
        if ($orders->isEmpty()) {
            abort(404, 'No orders found');
        }
        
        // Generate frontend URL for QR codes
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        
        // Get company information from settings (use first order's shop_id)
        $shopId = $orders->first()->shop_id;
        $companyInfo = [
            'company_name' => CompanySetting::getValue('company_name', 'YAAL Jewelry', $shopId),
            'company_address' => CompanySetting::getValue('company_address', "123 Business Street\nSuite 100\nNew York, NY 10001\nUnited States", $shopId),
            'company_phone' => CompanySetting::getValue('company_phone', '+1 (555) 123-4567', $shopId),
            'company_email' => CompanySetting::getValue('company_email', 'support@yaal.com', $shopId),
        ];
        
        return view('stickers.bulk-shipping-labels', [
            'orders' => $orders,
            'frontendUrl' => $frontendUrl,
            'companyInfo' => $companyInfo,
        ]);
    }
}