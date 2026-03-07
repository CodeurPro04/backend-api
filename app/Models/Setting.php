<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'description'];

    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    public static function set($key, $value, $type = 'string')
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => static::prepareValue($value, $type), 'type' => $type]
        );
    }

    private static function castValue($value, $type)
    {
        return match($type) {
            'boolean' => (bool) $value,
            'number' => is_numeric($value) ? (float) $value : $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    private static function prepareValue($value, $type)
    {
        return match($type) {
            'boolean' => $value ? '1' : '0',
            'number' => $value !== null ? (string) $value : null,
            'json' => $value !== null ? json_encode($value, JSON_UNESCAPED_UNICODE) : null,
            default => $value,
        };
    }
}
