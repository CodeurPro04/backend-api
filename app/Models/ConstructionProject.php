<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ConstructionProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'country_id', 'user_id', 'agent_id', 'partner_id', 'title', 'description',
        'project_type', 'budget_min', 'budget_max', 'surface_area',
        'location', 'city', 'latitude', 'longitude', 'status', 'is_publication', 'rejection_reason', 'plan_3d_path', 'documents_path',
        'images_path', 'plans_path', 'render_3d_path', 'estimated_duration', 'start_date', 'end_date'
    ];

    protected $casts = [
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'surface_area' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'documents_path' => 'array',
        'images_path' => 'array',
        'plans_path' => 'array',
        'render_3d_path' => 'array',
        'is_publication' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
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

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function partner()
    {
        return $this->belongsTo(Partnership::class, 'partner_id');
    }

    public function quotes()
    {
        return $this->hasMany(ConstructionQuote::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'submitted');
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }
}
