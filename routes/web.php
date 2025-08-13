<?php

use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\PurchaseResource\Pages\PurchaseEdit;
use App\Http\Controllers\StickerController;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Purchase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;

Route::get('/invoices/{record}/edit', [EditInvoice::class, 'edit'])->name('invoices.edit');
Route::get('/purchase/{record}/edit', [PurchaseEdit::class, 'edit'])->name('purchase.edit');

// Route::get('purchase/edit/{id}', PurchaseEdit::class)->name('purchase.edit');
Route::get('download-invoice/{invoice}', function (Invoice $invoice) {
    $invoice->load('items.product');

    // Pass the invoice data to the view
    $pdf = Pdf::loadView('invoices.print', compact('invoice'));

    // Download the PDF
    return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
})->name('download.invoice');

// Sticker printing routes
Route::get('/sticker/{order}', [StickerController::class, 'printSticker'])->name('sticker.print');
Route::get('/stickers/bulk', [StickerController::class, 'bulkPrintStickers'])->name('sticker.bulk-print');

// Order details route (for QR code)
Route::get('/order-details/{order}', function (Order $order) {
    $order->load('items.product');
    return view('orders.details', compact('order'));
})->name('order.details');
