<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;

class CountryController extends Controller
{
    public function index()
    {
        $countries = Country::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $countries,
        ]);
    }
}
