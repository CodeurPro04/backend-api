<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InvestmentProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'created_by', 'title', 'slug', 'description',
        'project_type', 'location', 'city', 'total_investment',
        'min_investment', 'expected_return', 'duration_months',
        'status', 'start_date', 'end_date', 'documents_path',
        'images_path', 'current_funding', 'investors_count', 'featured'
    ];

    protected $casts = [
        'total_investment' => 'decimal:2',
        'min_investment' => 'decimal:2',
        'expected_return' => 'decimal:2',
        'current_funding' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'documents_path' => 'array',
        'images_path' => 'array',
        'featured' => 'boolean',
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
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function proposals()
    {
        return $this->hasMany(InvestmentProposal::class);
    }

    public function approvedProposals()
    {
        return $this->hasMany(InvestmentProposal::class)->where('status', 'approved');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function getFundingPercentageAttribute(): float
    {
        if ($this->total_investment <= 0) {
            return 0;
        }
        return ($this->current_funding / $this->total_investment) * 100;
    }

    public function getRemainingInvestmentAttribute(): float
    {
        return max(0, $this->total_investment - $this->current_funding);
    }
}