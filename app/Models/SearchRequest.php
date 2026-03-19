<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SearchRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'user_id', 'agent_id', 'property_type_id',
        'transaction_type', 'budget_min', 'budget_max',
        'location_preferences', 'bedrooms_min', 'surface_min',
        'additional_requirements', 'status', 'priority',
        'rejection_reason', 'approved_at', 'rejected_at',
        'assigned_at', 'fulfilled_at', 'deal_status', 'deal_concluded_at',
        'deal_sale_price', 'deal_closure_note'
    ];

    protected $casts = [
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'surface_min' => 'decimal:2',
        'location_preferences' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'assigned_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'deal_concluded_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function propertyType()
    {
        return $this->belongsTo(PropertyType::class);
    }


    public function reports()
    {
        return $this->hasMany(SearchRequestReport::class)->orderBy('created_at');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAssigned($query)
    {
        return $query->whereNotNull('agent_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}