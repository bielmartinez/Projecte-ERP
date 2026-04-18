<?php

namespace App\Controllers;

use App\Models\FacturaModel;
use App\Models\MovimentModel;
use App\Models\QuotaModel;
use CodeIgniter\HTTP\ResponseInterface;

class DashboardController extends BaseController
{
    protected MovimentModel $movimentModel;
    protected FacturaModel $facturaModel;
    protected QuotaModel $quotaModel;

    /**
     * Inicialitza els models necessaris per al dashboard.
     */
    public function __construct()
    {
        $this->movimentModel = new MovimentModel();
        $this->facturaModel = new FacturaModel();
        $this->quotaModel = new QuotaModel();
    }

    /**
     * Retorna el resum econòmic del mes actual i de l'anterior.
     */
    public function resum(): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $mesActual = date('Y-m');
            $mesAnterior = date('Y-m', strtotime('first day of last month'));

            $resumMesActual = $this->movimentModel->resumMensual($usuariId, $mesActual);
            $resumMesAnterior = $this->movimentModel->resumMensual($usuariId, $mesAnterior);

            $data = [
                'mes_actual' => [
                    'periode' => $mesActual,
                    'ingressos' => round((float) ($resumMesActual['ingressos'] ?? 0), 2),
                    'despeses' => round((float) ($resumMesActual['despeses'] ?? 0), 2),
                    'benefici' => round(
                        (float) ($resumMesActual['ingressos'] ?? 0) - (float) ($resumMesActual['despeses'] ?? 0),
                        2
                    ),
                ],
                'mes_anterior' => [
                    'periode' => $mesAnterior,
                    'ingressos' => round((float) ($resumMesAnterior['ingressos'] ?? 0), 2),
                    'despeses' => round((float) ($resumMesAnterior['despeses'] ?? 0), 2),
                    'benefici' => round(
                        (float) ($resumMesAnterior['ingressos'] ?? 0) - (float) ($resumMesAnterior['despeses'] ?? 0),
                        2
                    ),
                ],
            ];

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en dashboard resum: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al carregar el resum del dashboard',
            ]);
        }
    }

    /**
     * Retorna les dades de gràfiques d'evolució i distribució de categories.
     */
    public function grafiques(): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $evolucioMensual = $this->movimentModel->evolucioMensual($usuariId, 12);
            $distribucioCategories = $this->movimentModel->distribucioCategoriesMes($usuariId);

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => [
                    'evolucio_mensual' => $evolucioMensual,
                    'distribucio_categories' => $distribucioCategories,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en dashboard grafiques: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al carregar les gràfiques del dashboard',
            ]);
        }
    }

    /**
     * Retorna les factures pendents de cobrament més urgents.
     */
    public function facturesPendents(): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $facturesPendents = $this->facturaModel->pendentsCobrament($usuariId);

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $facturesPendents,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en dashboard facturesPendents: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al carregar les factures pendents',
            ]);
        }
    }

    /**
     * Retorna les quotes amb períodes pendents de pagament.
     */
    public function quotesProperes(): ResponseInterface
    {
        try {
            $usuariId = user_id();

            if (!$usuariId) {
                return $this->response->setStatusCode(401)->setJSON([
                    'status' => 'error',
                    'message' => 'No autenticat',
                ]);
            }

            $quotes = $this->quotaModel->obtenirAmbPendents($usuariId);
            $quotesAmbPendents = array_values(array_filter(
                $quotes,
                static fn(array $quota): bool => (int) ($quota['periodes_pendents_count'] ?? 0) > 0
            ));

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => $quotesAmbPendents,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en dashboard quotesProperes: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al carregar les quotes properes',
            ]);
        }
    }
}
