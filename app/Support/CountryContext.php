<?php

namespace App\Support;

use App\Models\Country;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CountryContext
{
    public static function idFromCode(?string $code): ?int
    {
        if (!$code) {
            return null;
        }

        return Country::where('code', strtoupper(trim($code)))
            ->where('is_active', true)
            ->value('id');
    }

    public static function resolveIdFromRequest(Request $request): ?int
    {
        if ($request->filled('country_id')) {
            return Country::where('id', $request->input('country_id'))
                ->where('is_active', true)
                ->value('id');
        }

        return self::idFromCode(
            $request->input('country_code')
                ?: $request->header('X-Country-Code')
        );
    }

    public static function countryIdForUser(?User $user, Request $request): ?int
    {
        return self::resolveIdFromRequest($request) ?: $user?->country_id;
    }

    public static function applyPriority(Builder $query, Request $request, string $tableName): void
    {
        $countryId = self::resolveIdFromRequest($request);
        if (!$countryId || !Schema::hasColumn($tableName, 'country_id')) {
            return;
        }

        $query->orderByRaw("CASE WHEN {$tableName}.country_id = ? THEN 0 ELSE 1 END", [$countryId]);
    }
}
