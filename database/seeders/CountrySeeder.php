<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['code' => 'ZA', 'name' => 'Afrique du Sud', 'flag' => '🇿🇦'],
            ['code' => 'DZ', 'name' => 'Algérie', 'flag' => '🇩🇿'],
            ['code' => 'AO', 'name' => 'Angola', 'flag' => '🇦🇴'],
            ['code' => 'BJ', 'name' => 'Bénin', 'flag' => '🇧🇯'],
            ['code' => 'BW', 'name' => 'Botswana', 'flag' => '🇧🇼'],
            ['code' => 'BF', 'name' => 'Burkina Faso', 'flag' => '🇧🇫'],
            ['code' => 'BI', 'name' => 'Burundi', 'flag' => '🇧🇮'],
            ['code' => 'CM', 'name' => 'Cameroun', 'flag' => '🇨🇲'],
            ['code' => 'CV', 'name' => 'Cap-Vert', 'flag' => '🇨🇻'],
            ['code' => 'KM', 'name' => 'Comores', 'flag' => '🇰🇲'],
            ['code' => 'CG', 'name' => 'Congo', 'flag' => '🇨🇬'],
            ['code' => 'CI', 'name' => "Côte d'Ivoire", 'flag' => '🇨🇮', 'calling_code' => '+225', 'currency' => 'XOF'],
            ['code' => 'DJ', 'name' => 'Djibouti', 'flag' => '🇩🇯'],
            ['code' => 'EG', 'name' => 'Égypte', 'flag' => '🇪🇬'],
            ['code' => 'ER', 'name' => 'Érythrée', 'flag' => '🇪🇷'],
            ['code' => 'SZ', 'name' => 'Eswatini', 'flag' => '🇸🇿'],
            ['code' => 'ET', 'name' => 'Éthiopie', 'flag' => '🇪🇹'],
            ['code' => 'GA', 'name' => 'Gabon', 'flag' => '🇬🇦'],
            ['code' => 'GM', 'name' => 'Gambie', 'flag' => '🇬🇲'],
            ['code' => 'GH', 'name' => 'Ghana', 'flag' => '🇬🇭'],
            ['code' => 'GN', 'name' => 'Guinée', 'flag' => '🇬🇳'],
            ['code' => 'GW', 'name' => 'Guinée-Bissau', 'flag' => '🇬🇼'],
            ['code' => 'GQ', 'name' => 'Guinée équatoriale', 'flag' => '🇬🇶'],
            ['code' => 'KE', 'name' => 'Kenya', 'flag' => '🇰🇪'],
            ['code' => 'LS', 'name' => 'Lesotho', 'flag' => '🇱🇸'],
            ['code' => 'LR', 'name' => 'Libéria', 'flag' => '🇱🇷'],
            ['code' => 'LY', 'name' => 'Libye', 'flag' => '🇱🇾'],
            ['code' => 'MG', 'name' => 'Madagascar', 'flag' => '🇲🇬'],
            ['code' => 'MW', 'name' => 'Malawi', 'flag' => '🇲🇼'],
            ['code' => 'ML', 'name' => 'Mali', 'flag' => '🇲🇱'],
            ['code' => 'MA', 'name' => 'Maroc', 'flag' => '🇲🇦'],
            ['code' => 'MU', 'name' => 'Maurice', 'flag' => '🇲🇺'],
            ['code' => 'MR', 'name' => 'Mauritanie', 'flag' => '🇲🇷'],
            ['code' => 'MZ', 'name' => 'Mozambique', 'flag' => '🇲🇿'],
            ['code' => 'NA', 'name' => 'Namibie', 'flag' => '🇳🇦'],
            ['code' => 'NE', 'name' => 'Niger', 'flag' => '🇳🇪'],
            ['code' => 'NG', 'name' => 'Nigeria', 'flag' => '🇳🇬'],
            ['code' => 'UG', 'name' => 'Ouganda', 'flag' => '🇺🇬'],
            ['code' => 'CD', 'name' => 'RD Congo', 'flag' => '🇨🇩'],
            ['code' => 'CF', 'name' => 'Rép. centrafricaine', 'flag' => '🇨🇫'],
            ['code' => 'RW', 'name' => 'Rwanda', 'flag' => '🇷🇼'],
            ['code' => 'ST', 'name' => 'Sao Tomé-et-Principe', 'flag' => '🇸🇹'],
            ['code' => 'SN', 'name' => 'Sénégal', 'flag' => '🇸🇳', 'currency' => 'XOF'],
            ['code' => 'SC', 'name' => 'Seychelles', 'flag' => '🇸🇨'],
            ['code' => 'SL', 'name' => 'Sierra Leone', 'flag' => '🇸🇱'],
            ['code' => 'SO', 'name' => 'Somalie', 'flag' => '🇸🇴'],
            ['code' => 'SD', 'name' => 'Soudan', 'flag' => '🇸🇩'],
            ['code' => 'SS', 'name' => 'Soudan du Sud', 'flag' => '🇸🇸'],
            ['code' => 'TZ', 'name' => 'Tanzanie', 'flag' => '🇹🇿'],
            ['code' => 'TD', 'name' => 'Tchad', 'flag' => '🇹🇩'],
            ['code' => 'TG', 'name' => 'Togo', 'flag' => '🇹🇬', 'currency' => 'XOF'],
            ['code' => 'TN', 'name' => 'Tunisie', 'flag' => '🇹🇳'],
            ['code' => 'ZM', 'name' => 'Zambie', 'flag' => '🇿🇲'],
            ['code' => 'ZW', 'name' => 'Zimbabwe', 'flag' => '🇿🇼'],
        ];

        foreach ($countries as $order => $country) {
            Country::updateOrCreate(
                ['code' => $country['code']],
                $country + ['is_active' => true, 'display_order' => $order + 1]
            );
        }

        $this->backfillCountryLinks();
    }

    private function backfillCountryLinks(): void
    {
        $countryIds = Country::query()->where('is_active', true)->pluck('id');
        if ($countryIds->isEmpty()) {
            return;
        }

        if (Schema::hasColumn('users', 'country_id')) {
            DB::table('users')
                ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
                ->whereNull('users.country_id')
                ->whereNotIn('roles.slug', ['admin', 'gestionnaire', 'administrateur'])
                ->select('users.id')
                ->orderBy('users.id')
                ->get()
                ->each(fn ($user) => DB::table('users')->where('id', $user->id)->update([
                    'country_id' => $countryIds->random(),
                    'updated_at' => now(),
                ]));
        }

        $this->backfillFromUser('properties', 'user_id', $countryIds);
        $this->backfillFromUser('construction_projects', 'user_id', $countryIds);
        $this->backfillFromUser('investment_projects', 'created_by', $countryIds);
        $this->backfillFromUser('partnerships', 'user_id', $countryIds);
        $this->backfillPartnerProducts($countryIds);
        $this->backfillRandom('house_models', $countryIds);
        $this->backfillFromUser('search_requests', 'user_id', $countryIds);
        $this->backfillRequestCountry('client_requests', $countryIds);
        $this->backfillRequestCountry('property_requests', $countryIds);
    }

    private function backfillFromUser(string $table, string $userColumn, $countryIds): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'country_id')) {
            return;
        }

        DB::table($table)
            ->leftJoin('users', "users.id", '=', "{$table}.{$userColumn}")
            ->whereNull("{$table}.country_id")
            ->select("{$table}.id", 'users.country_id as user_country_id')
            ->orderBy("{$table}.id")
            ->get()
            ->each(fn ($row) => DB::table($table)->where('id', $row->id)->update([
                'country_id' => $row->user_country_id ?: $countryIds->random(),
                'updated_at' => now(),
            ]));
    }

    private function backfillPartnerProducts($countryIds): void
    {
        if (!Schema::hasTable('partner_products') || !Schema::hasColumn('partner_products', 'country_id')) {
            return;
        }

        DB::table('partner_products')
            ->leftJoin('partnerships', 'partnerships.id', '=', 'partner_products.partnership_id')
            ->whereNull('partner_products.country_id')
            ->select('partner_products.id', 'partnerships.country_id as partnership_country_id')
            ->orderBy('partner_products.id')
            ->get()
            ->each(fn ($product) => DB::table('partner_products')->where('id', $product->id)->update([
                'country_id' => $product->partnership_country_id ?: $countryIds->random(),
                'updated_at' => now(),
            ]));
    }

    private function backfillRequestCountry(string $table, $countryIds): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'country_id')) {
            return;
        }

        DB::table($table)
            ->whereNull('country_id')
            ->orderBy('id')
            ->get()
            ->each(function ($request) use ($table, $countryIds) {
                $countryId = null;

                if (isset($request->property_id) && $request->property_id) {
                    $countryId = DB::table('properties')->where('id', $request->property_id)->value('country_id');
                }
                if (!$countryId && isset($request->construction_project_id) && $request->construction_project_id) {
                    $countryId = DB::table('construction_projects')->where('id', $request->construction_project_id)->value('country_id');
                }
                if (!$countryId && isset($request->investment_project_id) && $request->investment_project_id) {
                    $countryId = DB::table('investment_projects')->where('id', $request->investment_project_id)->value('country_id');
                }
                if (!$countryId && isset($request->user_id) && $request->user_id) {
                    $countryId = DB::table('users')->where('id', $request->user_id)->value('country_id');
                }

                DB::table($table)->where('id', $request->id)->update([
                    'country_id' => $countryId ?: $countryIds->random(),
                    'updated_at' => now(),
                ]);
            });
    }

    private function backfillRandom(string $table, $countryIds): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'country_id')) {
            return;
        }

        DB::table($table)
            ->whereNull('country_id')
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(fn ($row) => DB::table($table)->where('id', $row->id)->update([
                'country_id' => $countryIds->random(),
                'updated_at' => now(),
            ]));
    }
}
