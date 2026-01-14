<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'user_id', 'agent_id', 'property_type_id', 'title', 'slug',
        'description', 'transaction_type', 'price', 'currency', 'negotiable',
        'surface_area', 'land_area', 'bedrooms', 'bathrooms', 'parking_spaces',
        'floor_number', 'total_floors', 'year_built', 'address', 'city',
        'commune', 'quartier', 'latitude', 'longitude', 'status',
        'rejection_reason', 'featured', 'views_count', 'published_at',
        'validated_at', 'validated_by'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'surface_area' => 'decimal:2',
        'land_area' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'negotiable' => 'boolean',
        'featured' => 'boolean',
        'published_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title) . '-' . Str::random(6);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('title') && !$model->isDirty('slug')) {
                $model->slug = Str::slug($model->title) . '-' . Str::random(6);
            }
        });
    }

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function propertyType()
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function features()
    {
        return $this->belongsToMany(
            PropertyFeature::class,
            'property_feature_pivot',
            'property_id',
            'feature_id'
        )->withTimestamps();
    }

    public function media()
    {
        return $this->hasMany(PropertyMedia::class)->orderBy('order');
    }

    public function images()
    {
        return $this->hasMany(PropertyMedia::class)->where('type', 'image')->orderBy('order');
    }

    public function primaryImage()
    {
        return $this->hasOne(PropertyMedia::class)->where('is_primary', true);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeForSale($query)
    {
        return $query->where('transaction_type', 'vente');
    }

    public function scopeForRent($query)
    {
        return $query->where('transaction_type', 'location');
    }

    public function scopeInCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopePriceBetween($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    // Helper methods
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'draft' && !empty($this->rejection_reason);
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'pending', 'rejected']);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' ' . $this->currency;
    }
}
