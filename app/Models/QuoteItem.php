<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id', 'category', 'description', 'quantity',
        'unit', 'unit_price', 'total_price', 'order'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($model) {
            $model->total_price = $model->quantity * $model->unit_price;
        });
    }

    public function quote()
    {
        return $this->belongsTo(ConstructionQuote::class, 'quote_id');
    }
}