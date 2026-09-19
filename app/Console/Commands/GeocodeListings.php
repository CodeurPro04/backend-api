<?php

namespace App\Console\Commands;

use App\Models\ConstructionProject;
use App\Models\InvestmentProject;
use App\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GeocodeListings extends Command
{
    protected $signature = 'listings:geocode {--force : Re-geocode rows that already have coordinates}';

    protected $description = "Geocode properties, construction and investment projects (address/city -> latitude/longitude) via Nominatim, for the homepage interactive map";

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->geocodeCollection(
            Property::query()->approved()->when(!$force, fn ($q) => $q->where(fn ($qq) => $qq->whereNull('latitude')->orWhereNull('longitude'))),
            fn ($property) => trim(($property->address ? $property->address . ', ' : '') . ($property->city ?: '') . ($property->country?->name ? ', ' . $property->country->name : '')),
            'Property'
        );

        $this->geocodeCollection(
            ConstructionProject::query()->where('status', 'published')->where('is_publication', true)->when(!$force, fn ($q) => $q->where(fn ($qq) => $qq->whereNull('latitude')->orWhereNull('longitude'))),
            fn ($project) => trim(($project->location ? $project->location . ', ' : '') . ($project->city ?: '') . ($project->country?->name ? ', ' . $project->country->name : '')),
            'ConstructionProject'
        );

        $this->geocodeCollection(
            InvestmentProject::query()->where('approval_status', 'approved')->when(!$force, fn ($q) => $q->where(fn ($qq) => $qq->whereNull('latitude')->orWhereNull('longitude'))),
            fn ($project) => trim(($project->location ? $project->location . ', ' : '') . ($project->city ?: '') . ($project->country?->name ? ', ' . $project->country->name : '')),
            'InvestmentProject'
        );

        $this->info('Geocoding termine.');
        return self::SUCCESS;
    }

    private function geocodeCollection($query, callable $addressBuilder, string $label): void
    {
        $rows = $query->with('country')->get();
        $this->info("{$label} : {$rows->count()} ligne(s) a traiter.");

        $done = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $address = $addressBuilder($row);

            if (blank($address)) {
                $failed++;
                continue;
            }

            $coords = $this->geocodeAddress($address);

            if ($coords) {
                $row->update([
                    'latitude' => $coords['lat'],
                    'longitude' => $coords['lon'],
                ]);
                $done++;
                $this->line("  OK  [{$label}#{$row->id}] {$address} -> {$coords['lat']}, {$coords['lon']}");
            } else {
                $failed++;
                $this->warn("  ECHEC [{$label}#{$row->id}] {$address}");
            }

            // Nominatim usage policy: max 1 requete/seconde.
            usleep(1_100_000);
        }

        $this->info("{$label} : {$done} geocode(s), {$failed} echec(s).");
    }

    private function geocodeAddress(string $address): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'AfricaBuildInvest/1.0 (contact@africabuildinvest.com)',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'json',
                'q' => $address,
                'limit' => 1,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $results = $response->json();

            if (empty($results)) {
                return null;
            }

            return [
                'lat' => (float) $results[0]['lat'],
                'lon' => (float) $results[0]['lon'],
            ];
        } catch (\Throwable $e) {
            $this->error("Erreur geocodage \"{$address}\": " . $e->getMessage());
            return null;
        }
    }
}
