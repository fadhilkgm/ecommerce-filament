<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Get tenant configuration
     */
    public function getConfig(Request $request): JsonResponse
    {
        try {
            $shopId = $request->get('shop_id', 1);
            
            // Get payment gateway setting from database or fallback to env
            $paymentGatewayEnabled = CompanySetting::getValue('payment_gateway_enabled', env('PAYMENT_GATEWAY_ENABLED', false), $shopId);
            
            // Build configuration from database settings
            $config = [
                'id' => 1,
                'shop_id' => $shopId,
                'payment_gateway_enabled' => $paymentGatewayEnabled,
                'payment_methods' => $paymentGatewayEnabled 
                    ? ['credit_card', 'paypal', 'bank_transfer'] 
                    : ['manual_payment'],
                'settings' => [
                    'currency' => CompanySetting::getValue('currency', 'USD', $shopId),
                    'tax_enabled' => CompanySetting::getValue('TAX_ENABLED', false, $shopId),
                    'tax_rate' => CompanySetting::getValue('tax_rate', 0.08, $shopId),
                    'free_shipping_threshold' => CompanySetting::getValue('free_shipping_threshold', 100, $shopId),
                    'default_shipping_cost' => CompanySetting::getValue('default_shipping_cost', 15.00, $shopId),
                    'use_stock_management' => CompanySetting::getValue('use_stock_management', true, $shopId),
                    'low_stock_threshold' => CompanySetting::getValue('low_stock_threshold', 10, $shopId),
                    'allow_backorders' => CompanySetting::getValue('allow_backorders', false, $shopId),
                    'UPI_LINK' => CompanySetting::getValue('UPI_LINK', null, $shopId),
                ],
                'company_info' => [
                    'company_name' => CompanySetting::getValue('company_name', 'YAAL Jewelry', $shopId),
                    'company_address' => CompanySetting::getValue('company_address', "123 Business Street\nSuite 100\nNew York, NY 10001\nUnited States", $shopId),
                    'company_phone' => CompanySetting::getValue('company_phone', '+1 (555) 123-4567', $shopId),
                    'company_email' => CompanySetting::getValue('company_email', 'support@yaal.com', $shopId),
                    'company_website' => CompanySetting::getValue('company_website', 'https://yaal.com', $shopId),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $config
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load tenant configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all company settings
     */
    public function getPublicSettings(Request $request): JsonResponse
    {
        try {
            $shopId = $request->get('shop_id', 1);
            
            $allSettings = CompanySetting::getAllSettings($shopId);

            return response()->json([
                'success' => true,
                'data' => $allSettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}