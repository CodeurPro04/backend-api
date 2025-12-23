<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyFeature extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'icon', 'category'];

    public function properties()
    {
        return $this->belongsToMany(
            Property::class,
            'property_feature_pivot',
            'feature_id',
            'property_id'
        )->withTimestamps();
    }
}