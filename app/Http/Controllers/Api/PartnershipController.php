<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Partnership;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PartnershipController extends Controller
{
    private function normalizeCatalog(?array $catalog): array
    {
        if (!is_array($catalog)) {
            return [];
        }

        return collect($catalog)
            ->map(function ($item) {
                $title = trim((string) data_get($item, 'title', ''));
                $description = trim((string) data_get($item, 'description', ''));

                if ($title === '' && $description === '') {
                    return null;
                }

                return [
                    'title' => $title,
                    'description' => $description,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function partnershipRules(bool $requireEmail = true, bool $emailUniqueOnUsers = false): array
    {
        $emailRule = $requireEmail ? 'required|email' : 'nullable|email';
        if ($emailUniqueOnUsers) {
            $emailRule .= '|unique:users,email';
        }

        return [
            'company_name' => 'required|string|max:255',
            'company_type' => 'required|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:100',
            'email' => $emailRule,
            'website' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'services' => 'nullable|array',
            'services.*' => 'string',
            'certifications' => 'nullable|array',
            'certifications.*' => 'string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ];
    }

    private function generateDefaultPassword(): string
    {
        return 'Abi@' . strtoupper(Str::random(2)) . random_int(100000, 999999);
    }

    private function createPartnershipRecord(array $validated, int $userId, ?string $logoPath = null): Partnership
    {
        return Partnership::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $userId,
            'company_name' => $validated['company_name'],
            'company_type' => $validated['company_type'],
            'registration_number' => $validated['registration_number'] ?? null,
            'tax_number' => $validated['tax_number'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
            'logo_path' => $logoPath,
            'description' => $validated['description'] ?? null,
            'services' => $validated['services'] ?? [],
            'certifications' => $validated['certifications'] ?? [],
            'status' => 'pending',
        ]);
    }

    public function publicApply(Request $request)
    {
        $validated = $request->validate($this->partnershipRules(true, true));

        $role = Role::where('slug', 'entreprise')->first();
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Le role entreprise est introuvable.',
            ], 500);
        }

        $defaultPassword = $this->generateDefaultPassword();
        $logoPath = null;

        DB::beginTransaction();

        try {
            $user = User::create([
                'first_name' => $validated['company_name'],
                'last_name' => 'Entreprise',
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($defaultPassword),
                'role_id' => $role->id,
                'is_active' => false,
            ]);

            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store("partnerships/{$user->uuid}/logo", 'public');
            }

            $application = $this->createPartnershipRecord($validated, $user->id, $logoPath);

            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'registered',
                'description' => 'Compte entreprise cree depuis le formulaire partenariat',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Demande partenaire envoyee et compte entreprise cree.',
                'data' => $application,
                'account' => [
                    'email' => $user->email,
                    'default_password' => $defaultPassword,
                    'requires_activation' => true,
                ],
            ], 201);
        } catch (\Throwable $exception) {
            DB::rollBack();

            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la creation du compte partenaire.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    // ENTREPRISE - Postuler a un partenariat
    public function apply(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate($this->partnershipRules(true, false));

        $logoPath = null;

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store("partnerships/{$user->uuid}/logo", 'public');
        }

        $application = $this->createPartnershipRecord($validated, $user->id, $logoPath);

        return response()->json([
            'success' => true,
            'data' => $application,
        ], 201);
    }

    // ENTREPRISE - Voir sa demande
    public function myApplication(Request $request)
    {
        $user = $request->user();
        $application = Partnership::where('user_id', $user->id)->latest()->first();

        return response()->json([
            'success' => true,
            'data' => $application,
        ]);
    }

    // ENTREPRISE - Mettre a jour sa demande
    public function update(Request $request)
    {
        $user = $request->user();
        $application = Partnership::where('user_id', $user->id)->latest()->firstOrFail();

        $validated = $request->validate($this->partnershipRules(true, false));

        $payload = $validated;
        unset($payload['logo']);

        if ($request->hasFile('logo')) {
            if ($application->logo_path) {
                Storage::disk('public')->delete($application->logo_path);
            }
            $payload['logo_path'] = $request->file('logo')->store("partnerships/{$application->uuid}/logo", 'public');
        }

        $payload['status'] = 'pending';
        $payload['approved_by'] = null;
        $payload['approved_at'] = null;
        $payload['rejection_reason'] = null;

        $application->update($payload);
        $user->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Demande mise a jour. Le compte repasse en attente de validation administrateur.',
            'data' => $application->fresh(),
        ]);
    }

    // ADMIN - Liste demandes en attente
    public function pending()
    {
        $applications = Partnership::with(['user', 'approver'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return response()->json($applications);
    }

    // ADMIN - Approuver demande
    public function approve($uuid)
    {
        $application = Partnership::where('uuid', $uuid)->firstOrFail();
        $application->status = 'approved';
        $application->approved_by = auth()->id();
        $application->approved_at = now();
        $application->save();

        if ($application->user) {
            $application->user->update(['is_active' => true]);
        }

        return response()->json(['success' => true]);
    }

    // ADMIN - Rejeter demande
    public function reject(Request $request, $uuid)
    {
        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $application = Partnership::where('uuid', $uuid)->firstOrFail();
        $application->status = 'rejected';
        $application->approved_by = auth()->id();
        $application->approved_at = now();
        $application->rejection_reason = $request->rejection_reason;
        $application->save();

        return response()->json(['success' => true]);
    }

    // PUBLIC - Liste des partenaires approuves
    public function publicApproved()
    {
        $partners = Partnership::where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $partners,
        ]);
    }

    // PUBLIC - Detail d'un partenaire approuve
    public function publicShow($uuid)
    {
        $partner = Partnership::where('uuid', $uuid)
            ->where('status', 'approved')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $partner,
        ]);
    }

    // ADMIN - Supprimer un partenariat
    public function destroy($uuid)
    {
        $application = Partnership::where('uuid', $uuid)->firstOrFail();

        if ($application->logo_path) {
            Storage::disk('public')->delete($application->logo_path);
        }

        $application->delete();

        return response()->json(['success' => true]);
    }

    // ADMIN - Liste toutes les demandes
    public function all()
    {
        $applications = Partnership::with(['user', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return response()->json($applications);
    }

    // ADMIN - Mise a jour du contenu public d'un partenaire
    public function updateContent(Request $request, $uuid)
    {
        $application = Partnership::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'profile_title' => 'nullable|string|max:255',
            'profile_description' => 'nullable|string',
            'service_offers' => 'nullable|array',
            'service_offers.*' => 'nullable|string|max:255',
            'product_showcase' => 'nullable|array',
            'product_showcase.*.title' => 'nullable|string|max:255',
            'product_showcase.*.description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_cover_image' => 'nullable|boolean',
        ]);

        $payload = [
            'profile_title' => $validated['profile_title'] ?? null,
            'profile_description' => $validated['profile_description'] ?? null,
            'service_offers' => collect($validated['service_offers'] ?? [])
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->values()
                ->all(),
            'product_showcase' => $this->normalizeCatalog($validated['product_showcase'] ?? []),
        ];

        if ($request->boolean('remove_cover_image') && $application->cover_image_path) {
            Storage::disk('public')->delete($application->cover_image_path);
            $payload['cover_image_path'] = null;
        }

        if ($request->hasFile('cover_image')) {
            if ($application->cover_image_path) {
                Storage::disk('public')->delete($application->cover_image_path);
            }
            $payload['cover_image_path'] = $request->file('cover_image')->store("partnerships/{$application->uuid}/cover", 'public');
        }

        $application->update($payload);

        return response()->json([
            'success' => true,
            'data' => $application->fresh(),
        ]);
    }
}
