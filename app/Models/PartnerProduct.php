<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PartnerProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'country_id', 'partnership_id', 'title', 'description',
        'price', 'currency', 'images', 'type_data',
        'status', 'rejection_reason', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'images'       => 'array',
        'type_data'    => 'array',
        'price'        => 'decimal:2',
        'approved_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($product) {
            if (empty($product->uuid)) {
                $product->uuid = (string) Str::uuid();
            }
        });
    }

    public function partnership()
    {
        return $this->belongsTo(Partnership::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
