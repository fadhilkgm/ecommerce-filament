<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'location',
        'phone',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function productAttributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function invoices(){
        return $this->hasMany(Invoice::class);
    }

    public function purchases(){
        return $this->hasMany(Purchase::class);
    }

    public function suppliers(){
        return $this->hasMany(Supplier::class);
    }

    public function supplierTransactions(){
        return $this->hasMany(SupplierTransaction::class);
    }

    public function transactions(){
        return $this->hasMany(Transaction::class);
    }

    public function masterData()
    {
        return $this->hasMany(MasterData::class);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
