<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Partnership extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'user_id', 'company_name', 'company_type',
        'registration_number', 'tax_number', 'address', 'city',
        'phone', 'email', 'website', 'logo_path', 'description',
        'services', 'certifications', 'status', 'approved_by', 'approved_at', 'rejection_reason'
    ];

    protected $casts = [
        'services' => 'array',
        'certifications' => 'array',
        'approved_at' => 'datetime',
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
        return $this->belongsTo(User::class);
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
