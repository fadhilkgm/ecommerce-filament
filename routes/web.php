<?php

use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\PurchaseResource\Pages\PurchaseEdit;
use App\Models\Invoice;
use App\Models\Purchase;
use Illuminate\Support\Facades\Route;
use Barryvdh\DomPDF\Facade\Pdf;



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