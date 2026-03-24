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
	$routes->post('/', 'FacturaController::create');
	$routes->put('(:num)', 'FacturaController::update/$1');
	$routes->delete('(:num)', 'FacturaController::delete/$1');
	$routes->put('(:num)/linies/(:num)', 'FacturaController::updateLinia/$1/$2');
	$routes->delete('(:num)/linies/(:num)', 'FacturaController::deleteLinia/$1/$2');
	$routes->put('(:num)/estat', 'FacturaController::canviarEstat/$1');
});
