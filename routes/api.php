
<?php

// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AIChatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PartnerProductController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\PropertyTypeController;
use App\Http\Controllers\Api\ConstructionProjectController;
use App\Http\Controllers\Api\InvestmentProjectController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\SearchRequestController;
use App\Http\Controllers\Api\PartnershipController;
use App\Http\Controllers\Api\PropertyRequestController;
use App\Http\Controllers\Api\ClientRequestController;
use App\Http\Controllers\Api\HouseModelController;
use App\Http\Controllers\Api\PresentationVideoController;
use App\Http\Controllers\Api\NavMenuAdController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Manager\ReportController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SystemStatusController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Routes publiques
Route::prefix('v1')->group(function () {
    Route::get('countries', [CountryController::class, 'index']);

    // Authentification
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    // Propriétés (public)
    Route::prefix('properties')->group(function () {
        Route::get('/', [PropertyController::class, 'index']);
        Route::get('/{uuid}', [PropertyController::class, 'show']);
        Route::get('/type/{slug}', [PropertyController::class, 'byType']);
        Route::get('/city/{city}', [PropertyController::class, 'byCity']);
        Route::get('/featured', [PropertyController::class, 'featured']);
    });

    // Types de propriétés
    Route::get('property-types', [PropertyTypeController::class, 'index']);
    Route::get('property-features', [PropertyTypeController::class, 'features']);

    // Media public (annonces approuvees)
    Route::get('media/public/{id}', [PropertyController::class, 'publicMedia']);

    // Projets d'investissement (public)
    Route::prefix('investments')->group(function () {
        Route::get('/', [InvestmentProjectController::class, 'index']);
        Route::get('/{uuid}', [InvestmentProjectController::class, 'show']);
    });

    // Projets de construction (liste publique)
    Route::get('construction-projects', [ConstructionProjectController::class, 'publicIndex']);
    Route::get('construction-projects/{uuid}', [ConstructionProjectController::class, 'publicShow']);
    // Demandes clients (public)
    Route::post('client-requests', [ClientRequestController::class, 'store'])->middleware('throttle:public-write');
    // Partenaires (public)
    Route::post('partnerships/apply', [PartnershipController::class, 'publicApply'])->middleware('throttle:public-write');
    Route::get('partnerships/approved', [PartnershipController::class, 'publicApproved']);
    Route::get('partnerships/lookup', [PartnershipController::class, 'lookup']);
    Route::get('partnerships/{uuid}', [PartnershipController::class, 'publicShow']);
    // Modeles de maison (public)
    Route::get('house-models', [HouseModelController::class, 'index']);
    Route::get('house-models/{identifier}', [HouseModelController::class, 'show']);
    // Section "Videos de presentation" page d'accueil (public)
    Route::get('presentation-video', [PresentationVideoController::class, 'show']);
    // Emplacements publicitaires des menus de la navbar (public)
    Route::get('nav-ads', [NavMenuAdController::class, 'index']);
    // Carte interactive - pins de tous les biens (public)
    Route::get('map-pins', [MapController::class, 'pins']);

    // Agents (public - page accueil)
    Route::get('agents/public', [UserManagementController::class, 'publicAgents']);

    // IA Chat — Akapko Manawa / Djuêdjuê / Koffi Gombo
    Route::post('ai/chat', [AIChatController::class, 'chat'])->middleware('throttle:ai-chat');

    // Produits / projets partenaires (public — approuvés uniquement)
    Route::get('partnerships/{uuid}/products',      [PartnerProductController::class, 'publicList']);
    Route::get('partnerships/{uuid}/construction',  [PartnerProductController::class, 'publicConstruction']);
    Route::get('partnerships/{uuid}/investments',   [PartnerProductController::class, 'publicInvestments']);

});

// Routes protégées
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Profil utilisateur
    Route::prefix('auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Media securise
    Route::get('/media/{id}', [PropertyController::class, 'media']);

    // Investissements (tous roles connectes)
    Route::prefix('investments')->group(function () {
        Route::get('/my-proposals', [InvestmentProjectController::class, 'myProposals']);
        Route::get('/proposals/{uuid}', [InvestmentProjectController::class, 'proposalDetails']);
        Route::post('/{uuid}/propose', [InvestmentProjectController::class, 'propose']);
    });

    // Demandes clients de l'utilisateur connecte (tous roles) — ex: formulaire "etre recontacte"
    // rempli en etant connecte (investisseur, proprietaire...)
    Route::get('/client-requests/mine', [ClientRequestController::class, 'myRequests']);

    // Agents disponibles pour demarrer une conversation (tous roles connectes,
    // utilise par le site public : un visiteur/proprietaire/investisseur ne peut
    // ecrire qu'a un agent).
    Route::get('/messages/agents', [MessageController::class, 'messageableAgents']);

    // Annuaire complet (tous roles) pour demarrer une conversation depuis le
    // backoffice : un agent/gestionnaire/admin/partenaire peut y chercher
    // n'importe quel autre utilisateur (agent, admin, gestionnaire, partenaire,
    // visiteur, proprietaire, investisseur...).
    // Reserve au backoffice (agent/gestionnaire/admin/partenaire) : un visiteur,
    // proprietaire ou investisseur ne doit pas pouvoir lister tous les comptes,
    // seulement les agents via /messages/agents ci-dessus.
    Route::middleware('checkrole:agent,gestionnaire,admin,entreprise')
        ->get('/messages/users', [MessageController::class, 'messageableUsers']);

    // Routes PROPRIÉTAIRE
    Route::middleware('checkrole:proprietaire')->prefix('proprietaire')->group(function () {
        Route::prefix('properties')->group(function () {
            Route::get('/my-properties', [PropertyController::class, 'myProperties']);
            Route::get('/{uuid}', [PropertyController::class, 'ownerShow']);
            Route::post('/', [PropertyController::class, 'store']);
            Route::put('/{uuid}', [PropertyController::class, 'update']);
            Route::delete('/{uuid}', [PropertyController::class, 'destroy']);
            Route::post('/{uuid}/add-images', [PropertyController::class, 'addImages']);
            Route::delete('/media/{id}', [PropertyController::class, 'deleteMedia']);
        });

        Route::prefix('property-requests')->group(function () {
            Route::get('/', [PropertyRequestController::class, 'myRequests']);
            Route::post('/', [PropertyRequestController::class, 'store']);
        });

        Route::prefix('messages')->group(function () {
            Route::get('/', [MessageController::class, 'ownerMessages']);
            Route::post('/', [MessageController::class, 'send'])->middleware('throttle:20,1');
            Route::get('/{uuid}', [MessageController::class, 'ownerShow']);
            Route::post('/{uuid}/reply', [MessageController::class, 'ownerReply'])->middleware('throttle:20,1');
            Route::post('/{uuid}/mark-read', [MessageController::class, 'ownerMarkRead']);
            Route::delete('/{uuid}', [MessageController::class, 'ownerDelete']);
        });
    });

    // Routes VISITEUR
    Route::middleware('checkrole:visiteur')->prefix('visiteur')->group(function () {
        // Messages
        Route::prefix('messages')->group(function () {
            Route::get('/', [MessageController::class, 'index']);
            Route::post('/', [MessageController::class, 'send'])->middleware('throttle:20,1');
            Route::get('/{uuid}', [MessageController::class, 'show']);
            Route::post('/{uuid}/reply', [MessageController::class, 'reply'])->middleware('throttle:20,1');
        });

        Route::prefix('client-requests')->group(function () {
            Route::post('/', [ClientRequestController::class, 'store']);
        });

        // Demandes de recherche
        Route::prefix('search-requests')->group(function () {
            Route::get('/', [SearchRequestController::class, 'myRequests']);
            Route::post('/', [SearchRequestController::class, 'store']);
            Route::get('/{uuid}', [SearchRequestController::class, 'show']);
        });

        // Projets de construction
        Route::prefix('construction')->group(function () {
            Route::post('/request', [ConstructionProjectController::class, 'submitRequest']);
            Route::get('/my-requests', [ConstructionProjectController::class, 'myRequests']);
        });
    });

    // Routes INVESTISSEUR
    Route::middleware('checkrole:investisseur')->prefix('investisseur')->group(function () {
        Route::prefix('investments')->group(function () {
            Route::get('/my-proposals', [InvestmentProjectController::class, 'myProposals']);
            Route::get('/proposals/{uuid}', [InvestmentProjectController::class, 'proposalDetails']);
        });

        // Messages (conversations avec les agents)
        Route::prefix('messages')->group(function () {
            Route::get('/', [MessageController::class, 'index']);
            Route::post('/', [MessageController::class, 'send'])->middleware('throttle:20,1');
            Route::get('/{uuid}', [MessageController::class, 'show']);
            Route::post('/{uuid}/reply', [MessageController::class, 'reply'])->middleware('throttle:20,1');
        });
    });

    // (route déplacée dans le prefix /investments ci-dessus)

    // Routes ENTREPRISE PARTENAIRE
    Route::middleware('checkrole:entreprise')->prefix('partnership')->group(function () {
        Route::post('/apply', [PartnershipController::class, 'apply']);
        Route::get('/my-application', [PartnershipController::class, 'myApplication']);
        Route::put('/update', [PartnershipController::class, 'update']);

        // Produits immobiliers (partenaire immobilier) → PartnerProduct
        Route::get('/products', [PartnerProductController::class, 'myProducts']);
        Route::post('/products', [PartnerProductController::class, 'store']);
        Route::put('/products/{uuid}', [PartnerProductController::class, 'update']);
        Route::delete('/products/{uuid}', [PartnerProductController::class, 'destroy']);

        // Projets de construction (partenaire constructeur) → ConstructionProject natif
        Route::get('/construction', [ConstructionProjectController::class, 'agentPublications']);
        Route::post('/construction', [ConstructionProjectController::class, 'agentCreate']);
        Route::put('/construction/{uuid}', [ConstructionProjectController::class, 'agentUpdate']);
        Route::delete('/construction/{uuid}', [ConstructionProjectController::class, 'staffDestroy']);

        // Projets d'investissement (partenaire investisseur) → InvestmentProject natif
        Route::get('/investments', [InvestmentProjectController::class, 'agentPublications']);
        Route::post('/investments', [InvestmentProjectController::class, 'agentCreate']);
        Route::put('/investments/{uuid}', [InvestmentProjectController::class, 'agentUpdate']);
        Route::delete('/investments/{uuid}', [InvestmentProjectController::class, 'destroy']);

        // Messages (conversations avec les agents, l'administration, etc.)
        Route::prefix('messages')->group(function () {
            Route::get('/', [MessageController::class, 'index']);
            Route::post('/', [MessageController::class, 'send'])->middleware('throttle:20,1');
            Route::get('/{uuid}', [MessageController::class, 'show']);
            Route::post('/{uuid}/reply', [MessageController::class, 'reply'])->middleware('throttle:20,1');
        });
    });

    // Routes AGENT IMMOBILIER
    Route::middleware('checkrole:agent')->prefix('agent')->group(function () {
        // Propriétés assignées
        Route::prefix('properties')->group(function () {
            Route::get('/assigned', [PropertyController::class, 'assignedProperties']);
            Route::post('/{uuid}/validate', [PropertyController::class, 'validate']);
            Route::post('/{uuid}/reject', [PropertyController::class, 'reject']);
            Route::post('/from-request/{uuid}', [PropertyController::class, 'agentStoreFromRequest']);
            Route::get('/all', [PropertyController::class, 'agentIndex']);
            Route::put('/{uuid}', [PropertyController::class, 'agentUpdate']);
            Route::delete('/media/{id}', [PropertyController::class, 'deleteMedia']);
        });

        // Messages clients
        Route::prefix('messages')->group(function () {
            Route::get('/', [MessageController::class, 'agentMessages']);
            Route::post('/', [MessageController::class, 'send'])->middleware('throttle:20,1');
            Route::get('/{uuid}', [MessageController::class, 'show']);
            Route::post('/{uuid}/respond', [MessageController::class, 'respond'])->middleware('throttle:20,1');
            Route::post('/{uuid}/mark-read', [MessageController::class, 'agentMarkRead']);
        });

        // Demandes de recherche
        Route::prefix('search-requests')->group(function () {
            Route::get('/assigned', [SearchRequestController::class, 'assignedRequests']);
            Route::get('/history', [SearchRequestController::class, 'agentHistory']);
            Route::post('/{uuid}/approve', [SearchRequestController::class, 'agentApprove']);
            Route::post('/{uuid}/reject', [SearchRequestController::class, 'agentReject']);
            Route::post('/{uuid}/reports', [SearchRequestController::class, 'addAgentReport']);
            Route::post('/{uuid}/conclude', [SearchRequestController::class, 'concludeDeal']);
            Route::post('/{uuid}/fulfill', [SearchRequestController::class, 'fulfill']);
        });

        Route::prefix('property-requests')->group(function () {
            Route::get('/assigned', [PropertyRequestController::class, 'assigned']);
            Route::post('/{uuid}/approve', [PropertyRequestController::class, 'agentApprove']);
            Route::post('/{uuid}/reject', [PropertyRequestController::class, 'agentReject']);
        });

        Route::prefix('client-requests')->group(function () {
            Route::get('/assigned', [ClientRequestController::class, 'agentAssigned']);
            Route::get('/history', [ClientRequestController::class, 'agentHistory']);
            Route::post('/{uuid}/approve', [ClientRequestController::class, 'agentApprove']);
            Route::post('/{uuid}/reject', [ClientRequestController::class, 'agentReject']);
            Route::post('/{uuid}/reports', [ClientRequestController::class, 'addAgentReport']);
            Route::post('/{uuid}/conclude', [ClientRequestController::class, 'concludeDeal']);
        });

        // Projets de construction
        Route::prefix('construction')->group(function () {
            Route::get('/assigned', [ConstructionProjectController::class, 'assignedProjects']);
            Route::post('/{uuid}/quote', [ConstructionProjectController::class, 'createQuote']);
            Route::get('/quotes', [ConstructionProjectController::class, 'myQuotes']);
            Route::get('/publications', [ConstructionProjectController::class, 'agentPublications']);
            Route::post('/publications', [ConstructionProjectController::class, 'agentCreate']);
            Route::put('/publications/{uuid}', [ConstructionProjectController::class, 'agentUpdate']);
        });

        Route::prefix('investments')->group(function () {
            Route::get('/publications', [InvestmentProjectController::class, 'agentPublications']);
            Route::post('/publications', [InvestmentProjectController::class, 'agentCreate']);
            Route::put('/publications/{uuid}', [InvestmentProjectController::class, 'agentUpdate']);
            // Propositions d'investissement a traiter (reserve aux agents investissement)
            Route::get('/proposals', [InvestmentProjectController::class, 'agentProposals']);
            Route::post('/proposals/{uuid}/approve', [InvestmentProjectController::class, 'agentApproveProposal']);
            Route::post('/proposals/{uuid}/reject', [InvestmentProjectController::class, 'agentRejectProposal']);
        });
    });

        // Routes GESTIONNAIRE
    Route::middleware('checkrole:gestionnaire')->prefix('gestionnaire')->group(function () {
        // Gestion des proprietes
        Route::prefix('properties')->group(function () {
            Route::get('/all', [PropertyController::class, 'managerIndex']);
            Route::get('/pending', [PropertyController::class, 'pending']);
            Route::post('/', [PropertyController::class, 'store']);
            Route::put('/{uuid}', [PropertyController::class, 'adminUpdate']);
            Route::post('/{uuid}/assign', [PropertyController::class, 'assign']);
            Route::post('/{uuid}/status', [PropertyController::class, 'staffUpdateStatus']);
            Route::delete('/media/{id}', [PropertyController::class, 'deleteMedia']);
        });

        // Gestion des demandes de recherche
        Route::prefix('search-requests')->group(function () {
            Route::get('/pending', [SearchRequestController::class, 'pending']);
            Route::get('/history', [SearchRequestController::class, 'managerHistory']);
            Route::post('/{uuid}/assign', [SearchRequestController::class, 'assignToAgent']);
            Route::post('/{uuid}/approve', [SearchRequestController::class, 'approve']);
            Route::post('/{uuid}/reject', [SearchRequestController::class, 'reject']);
        });

        // Gestion des projets de construction
        Route::prefix('construction')->group(function () {
            Route::get('/', [ConstructionProjectController::class, 'staffIndex']);
            Route::get('/all', [ConstructionProjectController::class, 'staffIndex']);
            Route::get('/pending', [ConstructionProjectController::class, 'pending']);
            Route::get('/history', [ConstructionProjectController::class, 'managerHistory']);
            Route::post('/spotlight', [ConstructionProjectController::class, 'updateSpotlightContent']);
            Route::post('/{uuid}/assign', [ConstructionProjectController::class, 'assign']);
            Route::post('/{uuid}/approve', [ConstructionProjectController::class, 'approve']);
            Route::post('/{uuid}/reject', [ConstructionProjectController::class, 'reject']);
            Route::post('/', [ConstructionProjectController::class, 'staffCreate']);
            Route::put('/{uuid}', [ConstructionProjectController::class, 'staffUpdate']);
            Route::delete('/{uuid}', [ConstructionProjectController::class, 'staffDestroy']);
        });

        Route::prefix('client-requests')->group(function () {
            Route::get('/pending', [ClientRequestController::class, 'pending']);
            Route::get('/history', [ClientRequestController::class, 'history']);
            Route::post('/{uuid}/approve', [ClientRequestController::class, 'approve']);
            Route::post('/{uuid}/reject', [ClientRequestController::class, 'reject']);
            Route::post('/{uuid}/assign', [ClientRequestController::class, 'assign']);
        });

        Route::prefix('property-requests')->group(function () {
            Route::get('/pending', [PropertyRequestController::class, 'pending']);
            Route::get('/history', [PropertyRequestController::class, 'history']);
            Route::post('/{uuid}/approve', [PropertyRequestController::class, 'approve']);
            Route::post('/{uuid}/reject', [PropertyRequestController::class, 'reject']);
            Route::post('/{uuid}/assign', [PropertyRequestController::class, 'assign']);
        });

        // Produits partenaires — validation gestionnaire
        Route::prefix('partner-products')->group(function () {
            Route::get('/pending', [PartnerProductController::class, 'pendingProducts']);
            Route::get('/all', [PartnerProductController::class, 'allProducts']);
            Route::post('/{uuid}/approve', [PartnerProductController::class, 'approve']);
            Route::post('/{uuid}/reject', [PartnerProductController::class, 'reject']);
        });

        // Rapports
        Route::get('/reports', [ReportController::class, 'index']);

        // Agents disponibles
        Route::get('/agents', [UserManagementController::class, 'availableAgents']);

        // Projets d'investissement
        Route::prefix('investments')->group(function () {
            Route::get('/', [InvestmentProjectController::class, 'staffIndex']);
            Route::post('/', [InvestmentProjectController::class, 'create']);
            Route::put('/{uuid}', [InvestmentProjectController::class, 'update']);
            Route::delete('/{uuid}', [InvestmentProjectController::class, 'destroy']);
            Route::post('/{uuid}/approve', [InvestmentProjectController::class, 'approveProject']);
            Route::post('/{uuid}/reject', [InvestmentProjectController::class, 'rejectProject']);
        });

        // Messages (conversations avec les agents, les autres membres du staff, etc.)
        Route::prefix('messages')->group(function () {
            Route::get('/', [MessageController::class, 'index']);
            Route::post('/', [MessageController::class, 'send'])->middleware('throttle:20,1');
            Route::get('/{uuid}', [MessageController::class, 'show']);
            Route::post('/{uuid}/reply', [MessageController::class, 'reply'])->middleware('throttle:20,1');
        });
    });


    // Routes ADMINISTRATEUR
    Route::middleware('checkrole:admin')->prefix('admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/statistics', [DashboardController::class, 'statistics']);

        // Etat systeme (sidebar)
        Route::get('/system/status', [SystemStatusController::class, 'index']);

        // Gestion des utilisateurs
        Route::prefix('users')->group(function () {
            Route::get('/', [UserManagementController::class, 'index']);
            Route::post('/', [UserManagementController::class, 'store']);
            Route::get('/{id}', [UserManagementController::class, 'show']);
            Route::put('/{id}', [UserManagementController::class, 'update']);
            Route::delete('/{id}', [UserManagementController::class, 'destroy']);
            Route::post('/{id}/toggle-status', [UserManagementController::class, 'toggleStatus']);
            Route::post('/{id}/assign-role', [UserManagementController::class, 'assignRole']);
        });
        Route::get('/agents', [UserManagementController::class, 'availableAgents']);

        // Gestion des rôles
        Route::prefix('checkroles')->group(function () {
            Route::get('/', [UserManagementController::class, 'checkroles']);
            Route::post('/', [UserManagementController::class, 'createRole']);
            Route::put('/{id}', [UserManagementController::class, 'updateRole']);
        });

        // Gestion complète des propriétés
        Route::prefix('properties')->group(function () {
            Route::get('/', [PropertyController::class, 'adminIndex']);
            Route::get('/all', [PropertyController::class, 'adminIndex']);
            Route::post('/', [PropertyController::class, 'adminStore']);
            Route::put('/{uuid}', [PropertyController::class, 'adminUpdate']);
            Route::delete('/{uuid}', [PropertyController::class, 'forceDelete']);
            Route::post('/{uuid}/toggle-featured', [PropertyController::class, 'toggleFeatured']);
            Route::post('/{uuid}/status', [PropertyController::class, 'staffUpdateStatus']);
            Route::delete('/media/{id}', [PropertyController::class, 'deleteMedia']);
        });


        // Gestion des messages
        Route::prefix('messages')->group(function () {
            Route::get('/', [MessageController::class, 'adminIndex']);
            Route::get('/{uuid}', [MessageController::class, 'adminShow']);
            Route::post('/', [MessageController::class, 'adminCreate'])->middleware('throttle:20,1');
            Route::put('/{uuid}', [MessageController::class, 'adminUpdate']);
            Route::delete('/{uuid}', [MessageController::class, 'adminDestroy']);
            Route::post('/{uuid}/mark-read', [MessageController::class, 'adminMarkRead']);
            Route::post('/{uuid}/reply', [MessageController::class, 'adminReply'])->middleware('throttle:20,1');
            Route::post('/{uuid}/archive', [MessageController::class, 'adminArchive']);
            Route::post('/{uuid}/unarchive', [MessageController::class, 'adminUnarchive']);
        });

        // Projets d'investissement
        Route::prefix('investments')->group(function () {
            Route::get('/', [InvestmentProjectController::class, 'staffIndex']);
            Route::post('/', [InvestmentProjectController::class, 'create']);
            Route::put('/{uuid}', [InvestmentProjectController::class, 'update']);
            Route::delete('/{uuid}', [InvestmentProjectController::class, 'destroy']);
            Route::get('/proposals', [InvestmentProjectController::class, 'allProposals']);
            Route::post('/proposals/{uuid}/approve', [InvestmentProjectController::class, 'approveProposal']);
            Route::post('/proposals/{uuid}/reject', [InvestmentProjectController::class, 'rejectProposal']);
            Route::post('/{uuid}/approve', [InvestmentProjectController::class, 'approveProject']);
            Route::post('/{uuid}/reject', [InvestmentProjectController::class, 'rejectProject']);
        });

        // Produits partenaires — validation admin
        Route::prefix('partner-products')->group(function () {
            Route::get('/pending', [PartnerProductController::class, 'pendingProducts']);
            Route::get('/all', [PartnerProductController::class, 'allProducts']);
            Route::post('/{uuid}/approve', [PartnerProductController::class, 'approve']);
            Route::post('/{uuid}/reject', [PartnerProductController::class, 'reject']);
        });

        // Partenariats
        Route::prefix('partnerships')->group(function () {
            Route::get('/pending', [PartnershipController::class, 'pending']);
            Route::post('/{uuid}/approve', [PartnershipController::class, 'approve']);
            Route::post('/{uuid}/reject', [PartnershipController::class, 'reject']);
            Route::get('/all', [PartnershipController::class, 'all']);
            Route::post('/{uuid}/content', [PartnershipController::class, 'updateContent']);
            Route::delete('/{uuid}', [PartnershipController::class, 'destroy']);
        });

        // Types de propriétés
        Route::prefix('property-types')->group(function () {
            Route::post('/', [PropertyTypeController::class, 'store']);
            Route::put('/{id}', [PropertyTypeController::class, 'update']);
            Route::delete('/{id}', [PropertyTypeController::class, 'destroy']);
        });

        // Caractéristiques
        Route::prefix('property-features')->group(function () {
            Route::post('/', [PropertyTypeController::class, 'storeFeature']);
            Route::put('/{id}', [PropertyTypeController::class, 'updateFeature']);
            Route::delete('/{id}', [PropertyTypeController::class, 'destroyFeature']);
        });

        // Paramètres système
        Route::prefix('settings')->group(function () {
            Route::get('/', [DashboardController::class, 'getSettings']);
            Route::post('/', [DashboardController::class, 'updateSettings']);
        });

        // Logs d'activité
        Route::get('/activity-logs', [DashboardController::class, 'activityLogs']);

        // Rapports
        Route::prefix('reports')->group(function () {
            Route::get('/properties', [DashboardController::class, 'propertiesReport']);
            Route::get('/users', [DashboardController::class, 'usersReport']);
            Route::get('/transactions', [DashboardController::class, 'transactionsReport']);
        });

        Route::prefix('search-requests')->group(function () {
            Route::get('/pending', [SearchRequestController::class, 'pending']);
            Route::get('/history', [SearchRequestController::class, 'managerHistory']);
            Route::post('/{uuid}/assign', [SearchRequestController::class, 'assignToAgent']);
            Route::post('/{uuid}/approve', [SearchRequestController::class, 'approve']);
            Route::post('/{uuid}/reject', [SearchRequestController::class, 'reject']);
        });

        Route::prefix('construction')->group(function () {
            Route::get('/', [ConstructionProjectController::class, 'staffIndex']);
            Route::get('/all', [ConstructionProjectController::class, 'staffIndex']);
            Route::get('/pending', [ConstructionProjectController::class, 'pending']);
            Route::get('/history', [ConstructionProjectController::class, 'managerHistory']);
            Route::post('/spotlight', [ConstructionProjectController::class, 'updateSpotlightContent']);
            Route::post('/{uuid}/assign', [ConstructionProjectController::class, 'assign']);
            Route::post('/{uuid}/approve', [ConstructionProjectController::class, 'approve']);
            Route::post('/{uuid}/reject', [ConstructionProjectController::class, 'reject']);
            Route::post('/', [ConstructionProjectController::class, 'staffCreate']);
            Route::put('/{uuid}', [ConstructionProjectController::class, 'staffUpdate']);
            Route::delete('/{uuid}', [ConstructionProjectController::class, 'staffDestroy']);
        });

        Route::prefix('property-requests')->group(function () {
            Route::get('/pending', [PropertyRequestController::class, 'pending']);
            Route::get('/history', [PropertyRequestController::class, 'history']);
            Route::post('/{uuid}/approve', [PropertyRequestController::class, 'approve']);
            Route::post('/{uuid}/reject', [PropertyRequestController::class, 'reject']);
            Route::post('/{uuid}/assign', [PropertyRequestController::class, 'assign']);
        });

        Route::prefix('client-requests')->group(function () {
            Route::get('/pending', [ClientRequestController::class, 'pending']);
            Route::get('/history', [ClientRequestController::class, 'history']);
            Route::post('/{uuid}/approve', [ClientRequestController::class, 'approve']);
            Route::post('/{uuid}/reject', [ClientRequestController::class, 'reject']);
            Route::post('/{uuid}/assign', [ClientRequestController::class, 'assign']);
        });

        // Modeles de maison (admin)
        Route::prefix('house-models')->group(function () {
            Route::get('/', [HouseModelController::class, 'adminIndex']);
            Route::post('/section', [HouseModelController::class, 'updateSection']);
            Route::post('/', [HouseModelController::class, 'store']);
            Route::put('/{uuid}', [HouseModelController::class, 'update']);
            Route::delete('/{uuid}', [HouseModelController::class, 'destroy']);
        });

        // Section "Videos de presentation" page d'accueil (admin)
        Route::prefix('presentation-video')->group(function () {
            Route::get('/', [PresentationVideoController::class, 'show']);
            Route::post('/', [PresentationVideoController::class, 'update']);
        });

        // Emplacements publicitaires des menus de la navbar (admin)
        Route::prefix('nav-ads')->group(function () {
            Route::get('/', [NavMenuAdController::class, 'adminIndex']);
            Route::post('/', [NavMenuAdController::class, 'update']);
        });
    });
});
