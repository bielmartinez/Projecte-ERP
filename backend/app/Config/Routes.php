<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('prueba-bd', 'ProvaBD::index');

$routes->group('auth', static function ($routes) {
	$routes->post('register', 'AuthController::register');
	$routes->post('login', 'AuthController::login');
	$routes->post('logout', 'AuthController::logout', ['filter' => 'auth']);
	$routes->get('me', 'AuthController::me', ['filter' => 'auth']);
});

$routes->group('perfil', ['filter' => 'auth'], static function ($routes) {
	$routes->get('/', 'PerfilController::getPerfil');
	$routes->put('/', 'PerfilController::updatePerfil');
	$routes->post('logo', 'PerfilController::pujarLogo');
	$routes->put('contrasenya', 'PerfilController::canviarContrasenya');
});

$routes->group('clients', ['filter' => 'auth'], static function ($routes) {
	$routes->get('/', 'ClientController::index');
	$routes->get('(:num)', 'ClientController::show/$1');
	$routes->post('/', 'ClientController::create');
	$routes->put('(:num)', 'ClientController::update/$1');
	$routes->delete('(:num)', 'ClientController::delete/$1');
});

$routes->group('factures', ['filter' => 'auth'], static function ($routes) {
	$routes->get('/', 'FacturaController::index');
	$routes->get('(:num)', 'FacturaController::show/$1');
	$routes->get('(:num)/pdf', 'FacturaController::pdf/$1');
	$routes->post('/', 'FacturaController::create');
	$routes->put('(:num)', 'FacturaController::update/$1');
	$routes->delete('(:num)', 'FacturaController::delete/$1');
	$routes->put('(:num)/linies/(:num)', 'FacturaController::updateLinia/$1/$2');
	$routes->delete('(:num)/linies/(:num)', 'FacturaController::deleteLinia/$1/$2');
	$routes->put('(:num)/estat', 'FacturaController::canviarEstat/$1');

	// Cobraments - delegat a CobramentController
	$routes->get('(:num)/cobraments', 'CobramentController::index/$1');
	$routes->post('(:num)/cobraments', 'CobramentController::create/$1');
	$routes->delete('(:num)/cobraments/(:num)', 'CobramentController::delete/$1/$2');
});

$routes->group('plantilles', ['filter' => 'auth'], static function ($routes) {
	$routes->get('/', 'PlantillaController::index');
	$routes->get('(:num)', 'PlantillaController::show/$1');
	$routes->post('/', 'PlantillaController::create');
	$routes->put('(:num)', 'PlantillaController::update/$1');
	$routes->delete('(:num)', 'PlantillaController::delete/$1');
});

$routes->group('categories', ['filter' => 'auth'], static function ($routes) {
	$routes->get('/', 'CategoriaController::index');
	$routes->get('(:num)', 'CategoriaController::show/$1');
	$routes->post('/', 'CategoriaController::create');
	$routes->put('(:num)', 'CategoriaController::update/$1');
	$routes->delete('(:num)', 'CategoriaController::delete/$1');
});

$routes->group('moviments', ['filter' => 'auth'], static function ($routes) {
	$routes->get('/', 'MovimentController::index');
	$routes->get('(:num)', 'MovimentController::show/$1');
	$routes->post('/', 'MovimentController::create');
	$routes->put('(:num)', 'MovimentController::update/$1');
	$routes->delete('(:num)', 'MovimentController::delete/$1');
});

$routes->group('quotes', ['filter' => 'auth'], static function ($routes) {
	$routes->get('/', 'QuotaController::index');
	$routes->get('(:num)', 'QuotaController::show/$1');
	$routes->post('/', 'QuotaController::create');
	$routes->put('(:num)', 'QuotaController::update/$1');
	$routes->delete('(:num)', 'QuotaController::delete/$1');
	$routes->post('(:num)/pagar', 'QuotaController::pagar/$1');
	$routes->get('(:num)/pagaments', 'QuotaController::pagaments/$1');
});

$routes->group('dashboard', ['filter' => 'auth'], static function ($routes) {
	$routes->get('resum', 'DashboardController::resum');
	$routes->get('grafiques', 'DashboardController::grafiques');
	$routes->get('factures-pendents', 'DashboardController::facturesPendents');
	$routes->get('quotes-properes', 'DashboardController::quotesProperes');
});

$routes->group('informes', ['filter' => 'auth'], static function ($routes) {
	$routes->get('mensual/(:num)/(:num)', 'InformeController::mensual/$1/$2');
	$routes->get('trimestral/(:num)/(:num)', 'InformeController::trimestral/$1/$2');
	$routes->get('anual/(:num)', 'InformeController::anual/$1');
	$routes->get('pdf/(:segment)/(:segment)', 'InformeController::pdf/$1/$2');
});

$routes->group('verifactu', ['filter' => 'auth'], static function ($routes) {
	$routes->get('registres', 'VerifactuController::index');
	$routes->get('registres/(:num)', 'VerifactuController::show/$1');
	$routes->get('validar', 'VerifactuController::validar');
	$routes->get('exportar', 'VerifactuController::exportar');
});
