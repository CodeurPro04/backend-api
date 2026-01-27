<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClientRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'property_id',
        'construction_project_id',
        'investment_project_id',
        'agent_id',
        'request_type',
        'name',
        'email',
        'phone',
        'message',
        'sector',
        'department',
        'project_description',
        'consent',
        'status',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'assigned_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'assigned_at' => 'datetime',
        'consent' => 'boolean',
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

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function constructionProject()
    {
        return $this->belongsTo(ConstructionProject::class, 'construction_project_id');
    }

    public function investmentProject()
    {
        return $this->belongsTo(InvestmentProject::class, 'investment_project_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
