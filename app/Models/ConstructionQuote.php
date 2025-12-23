<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConstructionQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'construction_project_id', 'agent_id', 'quote_number',
        'total_amount', 'currency', 'validity_days', 'status',
        'notes', 'file_path', 'sent_at', 'responded_at'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->quote_number)) {
                $model->quote_number = 'QT-' . date('Ymd') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function constructionProject()
    {
        return $this->belongsTo(ConstructionProject::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class, 'quote_id')->orderBy('order');
    }

    public function calculateTotal()
    {
        $this->total_amount = $this->items()->sum('total_price');
        $this->save();
    }
}