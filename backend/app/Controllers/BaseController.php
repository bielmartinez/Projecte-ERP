<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Retorna l'ID de l'usuari autenticat.
     * Només cridar dins rutes protegides amb AuthFilter.
     */
    protected function usuariId(): int
    {
        return (int) user_id();
    }

    /**
     * Retorna una resposta JSON d'èxit.
     */
    protected function jsonOk(array $data = [], int $code = 200): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON(array_merge(['status' => 'ok'], $data));
    }

    /**
     * Retorna una resposta JSON d'error.
     */
    protected function jsonError(string $message, int $code = 400, array $extra = []): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON(array_merge([
            'status' => 'error',
            'message' => $message,
        ], $extra));
    }
}
