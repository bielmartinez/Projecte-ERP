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
