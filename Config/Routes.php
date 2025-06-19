<?php 
require_once CORE_PATH . '/Router.php';

$router = new Router('/G11C/G11C_A1');

// ===== Routes d'authentification (centralisées) =====
$router->get('/', 'AuthController', 'showLogin');
$router->get('/login', 'AuthController', 'showLogin');
$router->post('/login', 'AuthController', 'login');
$router->get('/logout', 'AuthController', 'logout');
$router->get('/register', 'AuthController', 'showRegister');
$router->post('/register', 'AuthController', 'register');
$router->get('/mon_compte', 'AuthController', 'showAccount');
$router->post('/mon_compte/update', 'AuthController', 'updateAccount');
$router->post('/mon_compte/delete', 'AuthController', 'deleteAccount');

// ===== Routes Dashboard =====
$router->get('/dashboard2', 'DashboardController', 'index'); // dashboard2 devient la route principale
// $router->get('/dashboard', 'DashboardController', 'index'); // Optionnel : garder pour compatibilité
$router->get('/api/stats', 'DashboardController', 'getStats');
$router->get('/getManegesData', 'DashboardController', 'getManegesData');
$router->get('/api/alerts-count', 'DashboardController', 'getAlertsCount');

// ===== Routes Manèges =====
$router->get('/maneges', 'ManageController', 'index');
$router->get('/maneges/create', 'ManageController', 'create');
$router->post('/maneges', 'ManageController', 'store');
$router->get('/maneges/{id}', 'ManageController', 'show');
$router->get('/maneges/{id}/edit', 'ManageController', 'edit');
$router->put('/maneges/{id}', 'ManageController', 'update');
$router->delete('/maneges/{id}', 'ManageController', 'delete');

// ===== Routes Sessions =====
$router->get('/sessions', 'SessionController', 'index');
$router->get('/sessions/create', 'SessionController', 'create');
$router->post('/sessions', 'SessionController', 'store');
$router->get('/sessions/{id}', 'SessionController', 'show');
$router->post('/sessions/{id}/start', 'SessionController', 'start');
$router->post('/sessions/{id}/stop', 'SessionController', 'stop');
$router->get('/sessions/{id}/monitoring', 'SessionController', 'monitoring');

// ===== Routes Sécurité =====
$router->get('/security', 'SecurityController', 'index');
$router->get('/security/alerts', 'SecurityController', 'alerts');
$router->post('/security/alerts/{id}/acknowledge', 'SecurityController', 'acknowledgeAlert');
$router->get('/security/controls/{sessionId}', 'SecurityController', 'controls');
$router->post('/security/validate/{sessionId}', 'SecurityController', 'validateSecurity');

// ===== Routes TIVA/API =====
$router->get('/api/tiva/status', 'TivaController', 'getStatus');
$router->get('/api/tiva/realtime', 'TivaController', 'getRealTimeData');
$router->post('/api/tiva/calibrate', 'TivaController', 'calibrate');
$router->post('/api/tiva/command', 'TivaController', 'sendCommand');
$router->post('/api/tiva/reset', 'TivaController', 'reset');

// ===== Routes Admin =====
$router->get('/admin/dashboard', 'AdminController', 'dashboard');
$router->get('/admin/operateurs', 'AdminController', 'operateurs');
$router->get('/admin/config', 'AdminController', 'config');
$router->post('/admin/config', 'AdminController', 'updateConfig');

// ===== Routes Capteurs Température =====
$router->post('/api/sensors/reading', 'ApiTemperatureController', 'receiveReading');
$router->get('/api/sensors/readings', 'ApiTemperatureController', 'getReadings');
$router->get('/api/sensors/stats', 'ApiTemperatureController', 'getStats');
$router->get('/api/sensors/chart', 'ApiTemperatureController', 'getChartData');

return $router;