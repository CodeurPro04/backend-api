<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'uuid', 'role_id', 'first_name', 'last_name', 'email', 
        'phone', 'password', 'avatar', 'is_active'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
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

    // Relations
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'user_id');
    }

    public function assignedProperties()
    {
        return $this->hasMany(Property::class, 'agent_id');
    }

    public function constructionProjects()
    {
        return $this->hasMany(ConstructionProject::class, 'user_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function searchRequests()
    {
        return $this->hasMany(SearchRequest::class, 'user_id');
    }

    public function assignedSearchRequests()
    {
        return $this->hasMany(SearchRequest::class, 'agent_id');
    }

    public function investmentProposals()
    {
        return $this->hasMany(InvestmentProposal::class, 'user_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Helper methods
    public function hasRole($role): bool
    {
        return $this->role->slug === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isAgent(): bool
    {
        return $this->hasRole('agent');
    }

    public function isProprietaire(): bool
    {
        return $this->hasRole('proprietaire');
    }

    public function isVisiteur(): bool
    {
        return $this->hasRole('visiteur');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
