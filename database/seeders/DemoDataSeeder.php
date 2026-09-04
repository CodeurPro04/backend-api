<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\ClientRequest;
use App\Models\ClientRequestReport;
use App\Models\ConstructionProject;
use App\Models\ConstructionQuote;
use App\Models\Country;
use App\Models\HouseModel;
use App\Models\InvestmentProject;
use App\Models\InvestmentProposal;
use App\Models\Message;
use App\Models\Notification;
use App\Models\PartnerProduct;
use App\Models\Partnership;
use App\Models\Property;
use App\Models\PropertyFeature;
use App\Models\PropertyMedia;
use App\Models\PropertyRequest;
use App\Models\Role;
use App\Models\SearchRequest;
use App\Models\SearchRequestReport;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureSeedAssets();

        // ---- Users ---------------------------------------------------------
        $roles = Role::query()->pluck('id', 'slug');
        $countryIds = Country::query()->where('is_active', true)->pluck('id');

        if ($roles->isEmpty()) {
            $this->command?->warn('Aucun role trouvé: exécute RoleSeeder avant DemoDataSeeder.');
            return;
        }
        if ($countryIds->isEmpty()) {
            $this->call(CountrySeeder::class);
            $countryIds = Country::query()->where('is_active', true)->pluck('id');
        }

        // Managers / Agents / Owners / Visitors / Investors / Entreprises
        User::factory()->count(2)->role('gestionnaire')->create();
        User::factory()->count(8)->role('agent')->create(['agent_type' => fake()->randomElement(['constructeur', 'immobilier', 'investissement'])]);
        User::factory()->count(12)->role('proprietaire')->create();
        User::factory()->count(25)->role('visiteur')->create([
            'interests' => fake()->randomElements(['immobilier', 'construction', 'investissement'], fake()->numberBetween(1, 3)),
        ]);
        User::factory()->count(10)->role('investisseur')->create();
        User::factory()->count(8)->role('entreprise')->create();

        User::query()
            ->whereNull('country_id')
            ->whereHas('role', fn ($q) => $q->whereNotIn('slug', ['admin', 'gestionnaire', 'administrateur']))
            ->get()
            ->each(fn (User $user) => $user->update(['country_id' => $countryIds->random()]));

        $adminId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'admin'))->inRandomOrder()->value('id');
        $managerId = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'gestionnaire'))->inRandomOrder()->value('id');
        $agentIds = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'agent'))->pluck('id');
        $ownerIds = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'proprietaire'))->pluck('id');
        $visitorIds = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'visiteur'))->pluck('id');
        $investorIds = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'investisseur'))->pluck('id');
        $entrepriseIds = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'entreprise'))->pluck('id');

        // ---- Settings ------------------------------------------------------
        $settings = [
            ['key' => 'site_name', 'value' => 'NARAF', 'type' => 'string', 'description' => 'Nom du site'],
            ['key' => 'support_email', 'value' => 'support@naraf.local', 'type' => 'string', 'description' => 'Email support'],
            ['key' => 'default_currency', 'value' => 'XOF', 'type' => 'string', 'description' => 'Devise par défaut'],
            ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'description' => 'Mode maintenance'],
        ];
        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        // ---- Properties ----------------------------------------------------
        Property::factory()->count(60)->create();
        $featureIds = PropertyFeature::query()->pluck('id');
        $properties = Property::query()->latest()->take(60)->get();
        foreach ($properties as $property) {
            if ($featureIds->isNotEmpty()) {
                $picked = $featureIds->shuffle()->take(fake()->numberBetween(3, min(10, $featureIds->count())))->all();
                $property->features()->sync($picked);
            }

            // Seed media (all properties get at least one image)
            $imageCount = fake()->numberBetween(1, 5);
            $media = [];
            for ($i = 0; $i < $imageCount; $i++) {
                $media[] = [
                    'property_id' => $property->id,
                    'type' => 'image',
                    'file_path' => 'seed/placeholder.png',
                    'file_name' => 'placeholder.png',
                    'file_size' => 68,
                    'mime_type' => 'image/png',
                    'order' => $i,
                    'is_primary' => $i === 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            PropertyMedia::query()->insert($media);
        }

        // ---- Messages ------------------------------------------------------
        $someProperties = Property::query()->inRandomOrder()->take(20)->pluck('id');
        for ($i = 0; $i < 50; $i++) {
            $senderId = $visitorIds->random();
            $recipientId = $agentIds->random();
            $propertyId = fake()->optional(0.7)->passthrough($someProperties->random());

            $message = Message::factory()->create([
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'property_id' => $propertyId,
                'is_read' => fake()->boolean(35),
            ]);

            if (fake()->boolean(55)) {
                Message::factory()->count(fake()->numberBetween(1, 2))->create([
                    'sender_id' => $recipientId,
                    'recipient_id' => $senderId,
                    'property_id' => $propertyId,
                    'parent_message_id' => $message->id,
                ]);
            }
        }

        // ---- Search requests + reports ------------------------------------
        SearchRequest::factory()->count(25)->create();
        $assigned = SearchRequest::query()->whereNotNull('agent_id')->inRandomOrder()->take(12)->get();
        foreach ($assigned as $req) {
            SearchRequestReport::factory()->count(fake()->numberBetween(1, 3))->create([
                'search_request_id' => $req->id,
                'agent_id' => $req->agent_id,
            ]);
        }

        // ---- Construction projects + quotes/items -------------------------
        // Demandes (non publiques)
        ConstructionProject::factory()->count(12)->create([
            'status' => 'submitted',
            'is_publication' => false,
        ]);

        // Publications (page construction publique)
        ConstructionProject::factory()->count(12)->create([
            'status' => 'published',
            'is_publication' => true,
            'images_path' => ['seed/placeholder.png'],
            'plans_path' => ['seed/placeholder.png'],
            'documents_path' => ['seed/placeholder.pdf'],
        ]);

        // Divers (workflow interne)
        ConstructionProject::factory()->count(8)->create();
        $projectsForQuotes = ConstructionProject::query()->inRandomOrder()->take(10)->get();
        foreach ($projectsForQuotes as $project) {
            $quote = ConstructionQuote::factory()->create([
                'construction_project_id' => $project->id,
                'agent_id' => $project->agent_id ?? $agentIds->random(),
                'status' => fake()->randomElement(['sent', 'accepted', 'rejected']),
                'sent_at' => now()->subDays(fake()->numberBetween(1, 30)),
                'responded_at' => now()->subDays(fake()->numberBetween(0, 10)),
            ]);

            $items = \App\Models\QuoteItem::factory()->count(fake()->numberBetween(3, 8))->make([
                'quote_id' => $quote->id,
            ]);
            $total = 0;
            $order = 0;
            foreach ($items as $item) {
                $item->order = $order++;
                $total += (float) $item->total_price;
            }
            $quote->update(['total_amount' => $total]);
            $quote->items()->saveMany($items);
        }

        // ---- Investment projects + proposals ------------------------------
        InvestmentProject::factory()->count(14)->create();
        $investmentProjects = InvestmentProject::query()->inRandomOrder()->take(14)->get();
        foreach ($investmentProjects as $project) {
            $project->update([
                'images_path' => ['seed/placeholder.png'],
                'documents_path' => ['seed/placeholder.pdf'],
                'plans_path' => ['seed/placeholder.png'],
                'render_3d_path' => [],
            ]);
        }

        InvestmentProposal::factory()->count(40)->create();
        $projectsToAggregate = InvestmentProject::query()->get();
        foreach ($projectsToAggregate as $project) {
            $approvedProposals = $project->approvedProposals()->get();
            $funding = $approvedProposals->sum('amount');
            $count = $approvedProposals->pluck('user_id')->unique()->count();
            $project->update([
                'current_funding' => $funding,
                'investors_count' => $count,
                'approval_status' => $project->approval_status === 'pending' ? 'approved' : $project->approval_status,
            ]);
        }

        // ---- Partnerships + partner products ------------------------------
        Partnership::factory()->count(10)->create();
        $partnerships = Partnership::query()->inRandomOrder()->take(10)->get();
        foreach ($partnerships as $p) {
            $p->update([
                'logo_path' => 'seed/placeholder.png',
                'cover_image_path' => 'seed/placeholder.png',
            ]);
        }

        PartnerProduct::factory()->count(35)->create();

        // ---- House models --------------------------------------------------
        HouseModel::factory()->count(12)->create([
            'cover_image' => 'seed/placeholder.png',
            'gallery_images' => ['seed/placeholder.png', 'seed/placeholder.png'],
        ]);

        // ---- Client requests + reports ------------------------------------
        ClientRequest::factory()->count(20)->create();
        ClientRequestReport::factory()->count(18)->create();

        // ---- Property requests --------------------------------------------
        PropertyRequest::factory()->count(20)->create();

        // ---- Notifications -------------------------------------------------
        Notification::factory()->count(120)->create();

        // ---- Activity logs -------------------------------------------------
        ActivityLog::factory()->count(200)->create();

        $this->backfillCountries($countryIds);

        $this->command?->info('Demo data seeded successfully.');
    }

    private function backfillCountries($countryIds): void
    {
        Property::query()->whereNull('country_id')->with('user')->get()->each(function (Property $property) use ($countryIds) {
            $property->update(['country_id' => $property->user?->country_id ?: $countryIds->random()]);
        });

        ConstructionProject::query()->whereNull('country_id')->with('user')->get()->each(function (ConstructionProject $project) use ($countryIds) {
            $project->update(['country_id' => $project->user?->country_id ?: $countryIds->random()]);
        });

        InvestmentProject::query()->whereNull('country_id')->with('creator')->get()->each(function (InvestmentProject $project) use ($countryIds) {
            $project->update(['country_id' => $project->creator?->country_id ?: $countryIds->random()]);
        });

        Partnership::query()->whereNull('country_id')->with('user')->get()->each(function (Partnership $partnership) use ($countryIds) {
            $partnership->update(['country_id' => $partnership->user?->country_id ?: $countryIds->random()]);
        });

        PartnerProduct::query()->whereNull('country_id')->with('partnership')->get()->each(function (PartnerProduct $product) use ($countryIds) {
            $product->update(['country_id' => $product->partnership?->country_id ?: $countryIds->random()]);
        });

        HouseModel::query()->whereNull('country_id')->get()->each(fn (HouseModel $model) => $model->update(['country_id' => $countryIds->random()]));
        SearchRequest::query()->whereNull('country_id')->with('user')->get()->each(fn (SearchRequest $request) => $request->update(['country_id' => $request->user?->country_id ?: $countryIds->random()]));
        ClientRequest::query()->whereNull('country_id')->with('user')->get()->each(fn (ClientRequest $request) => $request->update(['country_id' => $request->user?->country_id ?: $countryIds->random()]));
        PropertyRequest::query()->whereNull('country_id')->with('user')->get()->each(fn (PropertyRequest $request) => $request->update(['country_id' => $request->user?->country_id ?: $countryIds->random()]));
    }

    private function ensureSeedAssets(): void
    {
        $disk = Storage::disk('public');

        if (!$disk->exists('seed/placeholder.png')) {
            $disk->makeDirectory('seed');
            // 1x1 transparent PNG
            $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/xcAAwMCAOeYfU0AAAAASUVORK5CYII=');
            $disk->put('seed/placeholder.png', $png);
        }

        if (!$disk->exists('seed/placeholder.pdf')) {
            $disk->makeDirectory('seed');
            $disk->put('seed/placeholder.pdf', "%PDF-1.4\n%âãÏÓ\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<<>>\n%%EOF\n");
        }
    }
}
