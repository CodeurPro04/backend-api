
<?php

// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\PropertyTypeController;
use App\Http\Controllers\Api\ConstructionProjectController;
use App\Http\Controllers\Api\InvestmentProjectController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\SearchRequestController;
use App\Http\Controllers\Api\PartnershipController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Manager\ReportController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Routes publiques
Route::prefix('v1')->group(function () {

    // Authentification
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
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
    // Partenaires approuves (public)
    Route::get('partnerships/approved', [PartnershipController::class, 'publicApproved']);

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

        Route::prefix('messages')->group(function () {
            Route::get('/', [MessageController::class, 'ownerMessages']);
            Route::get('/{uuid}', [MessageController::class, 'ownerShow']);
            Route::post('/{uuid}/reply', [MessageController::class, 'ownerReply']);
            Route::post('/{uuid}/mark-read', [MessageController::class, 'ownerMarkRead']);
            Route::delete('/{uuid}', [MessageController::class, 'ownerDelete']);
        });
    });

    // Routes VISITEUR
    Route::middleware('checkrole:visiteur')->prefix('visiteur')->group(function () {
        // Messages
        Route::prefix('messages')->group(function () {
            Route::get('/', [MessageController::class, 'index']);
            Route::post('/', [MessageController::class, 'send']);
            Route::get('/{uuid}', [MessageController::class, 'show']);
            Route::post('/{uuid}/reply', [MessageController::class, 'reply']);
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
            Route::post('/{uuid}/propose', [InvestmentProjectController::class, 'propose']);
            Route::get('/my-proposals', [InvestmentProjectController::class, 'myProposals']);
            Route::get('/proposals/{uuid}', [InvestmentProjectController::class, 'proposalDetails']);
        });
    });

    // Routes ENTREPRISE PARTENAIRE
    Route::middleware('checkrole:entreprise')->prefix('partnership')->group(function () {
        Route::post('/apply', [PartnershipController::class, 'apply']);
        Route::get('/my-application', [PartnershipController::class, 'myApplication']);
        Route::put('/update', [PartnershipController::class, 'update']);
    });

    // Routes AGENT IMMOBILIER
    Route::middleware('checkrole:agent')->prefix('agent')->group(function () {
        // Propriétés assignées
        Route::prefix('properties')->group(function () {
            Route::get('/assigned', [PropertyController::class, 'assignedProperties']);
            Route::post('/{uuid}/validate', [PropertyController::class, 'validate']);
            Route::post('/{uuid}/reject', [PropertyController::class, 'reject']);
        });

        // Messages clients
        Route::prefix('messages')->group(function () {
            Route::get('/', [MessageController::class, 'agentMessages']);
            Route::post('/', [MessageController::class, 'send']);
            Route::post('/{uuid}/respond', [MessageController::class, 'respond']);
            Route::post('/{uuid}/mark-read', [MessageController::class, 'agentMarkRead']);
        });

        // Demandes de recherche
        Route::prefix('search-requests')->group(function () {
            Route::get('/assigned', [SearchRequestController::class, 'assignedRequests']);
            Route::post('/{uuid}/fulfill', [SearchRequestController::class, 'fulfill']);
        });

        // Projets de construction
        Route::prefix('construction')->group(function () {
            Route::get('/assigned', [ConstructionProjectController::class, 'assignedProjects']);
            Route::post('/{uuid}/quote', [ConstructionProjectController::class, 'createQuote']);
            Route::get('/quotes', [ConstructionProjectController::class, 'myQuotes']);
        });
    });

        // Routes GESTIONNAIRE
    Route::middleware('checkrole:gestionnaire')->prefix('gestionnaire')->group(function () {
        // Gestion des proprietes
        Route::prefix('properties')->group(function () {
            Route::get('/all', [PropertyController::class, 'managerIndex']);
            Route::get('/pending', [PropertyController::class, 'pending']);
            Route::post('/{uuid}/assign', [PropertyController::class, 'assign']);
        });

        // Gestion des demandes de recherche
        Route::prefix('search-requests')->group(function () {
            Route::get('/pending', [SearchRequestController::class, 'pending']);
            Route::get('/history', [SearchRequestController::class, 'managerHistory']);
            Route::post('/{uuid}/assign', [SearchRequestController::class, 'assignToAgent']);
        });

        // Gestion des projets de construction
        Route::prefix('construction')->group(function () {
            Route::get('/pending', [ConstructionProjectController::class, 'pending']);
            Route::get('/history', [ConstructionProjectController::class, 'managerHistory']);
            Route::post('/{uuid}/assign', [ConstructionProjectController::class, 'assign']);
        });

        // Rapports
        Route::get('/reports', [ReportController::class, 'index']);

        // Agents disponibles
        Route::get('/agents', [UserManagementController::class, 'availableAgents']);
    });


    // Routes ADMINISTRATEUR
    Route::middleware('checkrole:admin')->prefix('admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/statistics', [DashboardController::class, 'statistics']);

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
        });


        // Gestion des messages
        Route::prefix('messages')->group(function () {
            Route::get('/', [MessageController::class, 'adminIndex']);
            Route::get('/{uuid}', [MessageController::class, 'adminShow']);
            Route::post('/', [MessageController::class, 'adminCreate']);
            Route::put('/{uuid}', [MessageController::class, 'adminUpdate']);
            Route::delete('/{uuid}', [MessageController::class, 'adminDestroy']);
            Route::post('/{uuid}/mark-read', [MessageController::class, 'adminMarkRead']);
            Route::post('/{uuid}/reply', [MessageController::class, 'adminReply']);
        });

        // Projets d'investissement
        Route::prefix('investments')->group(function () {
            Route::post('/', [InvestmentProjectController::class, 'create']);
            Route::put('/{uuid}', [InvestmentProjectController::class, 'update']);
            Route::delete('/{uuid}', [InvestmentProjectController::class, 'destroy']);
            Route::get('/proposals', [InvestmentProjectController::class, 'allProposals']);
            Route::post('/proposals/{uuid}/approve', [InvestmentProjectController::class, 'approveProposal']);
            Route::post('/proposals/{uuid}/reject', [InvestmentProjectController::class, 'rejectProposal']);
        });

        // Partenariats
        Route::prefix('partnerships')->group(function () {
            Route::get('/pending', [PartnershipController::class, 'pending']);
            Route::post('/{uuid}/approve', [PartnershipController::class, 'approve']);
            Route::post('/{uuid}/reject', [PartnershipController::class, 'reject']);
            Route::get('/all', [PartnershipController::class, 'all']);
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
    });
});
