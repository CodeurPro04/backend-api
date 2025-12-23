<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Property;
use App\Models\InvestmentProject;
use App\Models\Partnership;

class DashboardController extends Controller
{
    // Vue principale du dashboard
    public function index()
    {
        return response()->json([
            'users_count' => User::count(),
            'properties_count' => Property::count(),
            'investment_projects_count' => InvestmentProject::count(),
            'partnerships_count' => Partnership::count(),
        ]);
    }

    // Statistiques détaillées
    public function statistics()
    {
        // Exemple de statistiques personnalisées
        $data = [
            'users_per_role' => User::selectRaw('role_id, count(*) as count')->groupBy('role_id')->get(),
            'properties_status' => Property::selectRaw('status, count(*) as count')->groupBy('status')->get(),
            // Autres stats...
        ];

        return response()->json($data);
    }

    // Paramètres système
    public function getSettings()
    {
        // Charger les paramètres depuis une table ou config
        $settings = [
            'site_name' => config('app.name'),
            'maintenance_mode' => false,
        ];

        return response()->json($settings);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'string',
            'maintenance_mode' => 'boolean',
        ]);

        // Mettre à jour les paramètres en base ou config...

        return response()->json(['message' => 'Settings updated']);
    }

    // Logs d'activité (exemple simple)
    public function activityLogs()
    {
        // Supposons que tu utilises un package de logs en base
        // Ici exemple fictif
        $logs = []; // Récupérer les logs depuis DB

        return response()->json($logs);
    }

    // Rapports
    public function propertiesReport()
    {
        // Exemple rapport
        $data = Property::selectRaw('city, count(*) as count')->groupBy('city')->get();
        return response()->json($data);
    }

    public function usersReport()
    {
        $data = User::selectRaw('role_id, count(*) as count')->groupBy('role_id')->get();
        return response()->json($data);
    }

    public function transactionsReport()
    {
        // Tu peux connecter avec ta table transactions
        $data = []; // Exemple vide
        return response()->json($data);
    }
}
