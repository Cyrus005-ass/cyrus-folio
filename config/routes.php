<?php

use App\Core\Router;
use App\Controllers\AnalyticsController;
use App\Controllers\AuthController;
use App\Controllers\CertificationController;
use App\Controllers\ChatbotController;
use App\Controllers\CollaborationController;
use App\Controllers\ContactController;
use App\Controllers\DashboardController;
use App\Controllers\NotificationController;
use App\Controllers\PostController;
use App\Controllers\ProfileController;
use App\Controllers\ProjectController;
use App\Controllers\PublicController;
use App\Controllers\SkillController;
use App\Controllers\ThemeController;

$router = new Router();

// Public
$router->get('/', [PublicController::class, 'home']);
$router->get('/projects', [PublicController::class, 'projects']);
$router->get('/projects/{slug}', [PublicController::class, 'projectDetail']);
$router->get('/skills', [PublicController::class, 'skills']);
$router->get('/certifications', [PublicController::class, 'certifications']);
$router->get('/blog', [PublicController::class, 'blog']);
$router->get('/blog/{slug}', [PublicController::class, 'blogDetail']);
$router->get('/contact', [PublicController::class, 'contact']);
$router->post('/contact', [ContactController::class, 'store']);
$router->get('/about', [PublicController::class, 'about']);

// Auth admin
$router->get('/admin/login', [AuthController::class, 'loginForm']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->get('/admin/2fa/verify', [AuthController::class, 'twoFactorForm']);
$router->post('/admin/2fa/verify', [AuthController::class, 'twoFactorVerify']);
$router->post('/admin/logout', [AuthController::class, 'logout']);

// Admin
$router->get('/admin', [DashboardController::class, 'index']);
$router->get('/admin/projects', [ProjectController::class, 'adminIndex']);
$router->get('/admin/projects/create', [ProjectController::class, 'create']);
$router->post('/admin/projects', [ProjectController::class, 'store']);
$router->get('/admin/projects/{id}/edit', [ProjectController::class, 'edit']);
$router->put('/admin/projects/{id}', [ProjectController::class, 'update']);
$router->delete('/admin/projects/{id}', [ProjectController::class, 'destroy']);

$router->get('/admin/skills', [SkillController::class, 'adminIndex']);
$router->post('/admin/skills', [SkillController::class, 'store']);
$router->put('/admin/skills/{id}', [SkillController::class, 'update']);
$router->delete('/admin/skills/{id}', [SkillController::class, 'destroy']);

$router->get('/admin/certifications', [CertificationController::class, 'adminIndex']);
$router->get('/admin/certifications/create', [CertificationController::class, 'create']);
$router->post('/admin/certifications', [CertificationController::class, 'store']);
$router->get('/admin/certifications/{id}/edit', [CertificationController::class, 'edit']);
$router->put('/admin/certifications/{id}', [CertificationController::class, 'update']);
$router->delete('/admin/certifications/{id}', [CertificationController::class, 'destroy']);

$router->get('/admin/blog', [PostController::class, 'adminIndex']);
$router->get('/admin/blog/create', [PostController::class, 'create']);
$router->post('/admin/blog', [PostController::class, 'store']);
$router->get('/admin/blog/{id}/edit', [PostController::class, 'edit']);
$router->put('/admin/blog/{id}', [PostController::class, 'update']);
$router->delete('/admin/blog/{id}', [PostController::class, 'destroy']);

$router->get('/admin/collaborations', [CollaborationController::class, 'adminIndex']);
$router->post('/admin/collaborations', [CollaborationController::class, 'store']);
$router->put('/admin/collaborations/{id}', [CollaborationController::class, 'update']);
$router->delete('/admin/collaborations/{id}', [CollaborationController::class, 'destroy']);

$router->get('/admin/messages', [ContactController::class, 'adminIndex']);
$router->get('/admin/messages/{id}', [ContactController::class, 'show']);
$router->put('/admin/messages/{id}/read', [ContactController::class, 'markRead']);
$router->delete('/admin/messages/{id}', [ContactController::class, 'destroy']);
$router->get('/admin/theme', [ThemeController::class, 'adminIndex']);
$router->post('/admin/theme', [ThemeController::class, 'save']);
$router->post('/admin/theme/reset', [ThemeController::class, 'reset']);
$router->get('/admin/notifications', [NotificationController::class, 'adminIndex']);
$router->put('/admin/notifications/{id}/read', [NotificationController::class, 'read']);
$router->post('/admin/notifications/read-all', [NotificationController::class, 'readAll']);
$router->get('/admin/notifications/{id}/open', [NotificationController::class, 'open']);
$router->get('/admin/analytics', [AnalyticsController::class, 'adminIndex']);
$router->get('/admin/chatbot', [ChatbotController::class, 'adminIndex']);
$router->post('/admin/chatbot/knowledge', [ChatbotController::class, 'storeKnowledge']);
$router->put('/admin/chatbot/knowledge/{id}', [ChatbotController::class, 'updateKnowledge']);
$router->delete('/admin/chatbot/knowledge/{id}', [ChatbotController::class, 'destroyKnowledge']);
$router->post('/admin/chatbot/test', [ChatbotController::class, 'testMessage']);
$router->get('/admin/profile', [ProfileController::class, 'adminIndex']);
$router->post('/admin/profile', [ProfileController::class, 'save']);
$router->post('/admin/profile/password', [ProfileController::class, 'password']);
$router->post('/admin/profile/2fa/enable', [ProfileController::class, 'enableTwoFactor']);
$router->post('/admin/profile/2fa/disable', [ProfileController::class, 'disableTwoFactor']);

// API REST v1
$router->post('/api/v1/auth/login', [AuthController::class, 'login']);
$router->post('/api/v1/auth/2fa', [AuthController::class, 'twoFactorVerify']);
$router->post('/api/v1/auth/logout', [AuthController::class, 'logout']);

$router->get('/api/v1/projects', [ProjectController::class, 'indexApi']);
$router->post('/api/v1/projects', [ProjectController::class, 'storeApi']);
$router->put('/api/v1/projects/{id}', [ProjectController::class, 'updateApi']);
$router->delete('/api/v1/projects/{id}', [ProjectController::class, 'destroyApi']);

$router->get('/api/v1/skills', [SkillController::class, 'indexApi']);
$router->post('/api/v1/skills', [SkillController::class, 'storeApi']);
$router->put('/api/v1/skills/{id}', [SkillController::class, 'updateApi']);
$router->delete('/api/v1/skills/{id}', [SkillController::class, 'destroyApi']);

$router->get('/api/v1/certifications', [CertificationController::class, 'indexApi']);
$router->post('/api/v1/certifications', [CertificationController::class, 'storeApi']);
$router->put('/api/v1/certifications/{id}', [CertificationController::class, 'updateApi']);
$router->delete('/api/v1/certifications/{id}', [CertificationController::class, 'destroyApi']);

$router->get('/api/v1/posts', [PostController::class, 'indexApi']);
$router->post('/api/v1/posts', [PostController::class, 'storeApi']);
$router->put('/api/v1/posts/{id}', [PostController::class, 'updateApi']);
$router->delete('/api/v1/posts/{id}', [PostController::class, 'destroyApi']);

$router->post('/api/v1/contacts', [ContactController::class, 'storeApi']);
$router->get('/api/v1/contacts', [ContactController::class, 'indexApi']);

$router->post('/api/v1/messages', [ContactController::class, 'storeApi']);
$router->get('/api/v1/messages', [ContactController::class, 'liveIndexApi']);
$router->get('/api/v1/messages/{id}', [ContactController::class, 'liveShowApi']);
$router->put('/api/v1/messages/{id}/read', [ContactController::class, 'markReadApi']);
$router->delete('/api/v1/messages/{id}', [ContactController::class, 'destroyApi']);

$router->get('/api/v1/notifications', [NotificationController::class, 'indexApi']);
$router->put('/api/v1/notifications/{id}/read', [NotificationController::class, 'read']);

$router->post('/api/v1/chatbot/message', [ChatbotController::class, 'message']);
$router->get('/api/v1/chatbot/knowledge', [ChatbotController::class, 'knowledgeApi']);
$router->post('/api/v1/chatbot/knowledge', [ChatbotController::class, 'storeKnowledge']);

$router->get('/api/v1/analytics/summary', [AnalyticsController::class, 'summaryApi']);
$router->get('/api/v1/analytics/pages', [AnalyticsController::class, 'pagesApi']);

return $router;