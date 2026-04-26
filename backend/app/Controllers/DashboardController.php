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
     * Inicialitza els models i serveis: MovimentModel, FacturaModel i QuotaModel.
     */
    public function __construct()
    {
        $this->movimentModel = new MovimentModel();
        $this->facturaModel = new FacturaModel();
        $this->quotaModel = new QuotaModel();
    }
    /**
     * Calcula el resum econòmic del mes actual i del mes anterior.
     *
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function resum(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

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

            return $this->jsonOk(['data' => $data]);
        } catch (\Exception $e) {
            log_message('error', 'Error en dashboard resum: ' . $e->getMessage());

            return $this->jsonError('Error al carregar el resum del dashboard', 500);
        }
    }
    /**
     * Retorna les dades d'evolució mensual i distribució per categories.
     *
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function grafiques(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $evolucioMensual = $this->movimentModel->evolucioMensual($usuariId, 12);
            $distribucioCategories = $this->movimentModel->distribucioCategoriesMes($usuariId);

            return $this->jsonOk([
                'data' => [
                    'evolucio_mensual' => $evolucioMensual,
                    'distribucio_categories' => $distribucioCategories,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en dashboard grafiques: ' . $e->getMessage());

            return $this->jsonError('Error al carregar les gràfiques del dashboard', 500);
        }
    }
    /**
     * Llista les factures pendents de cobrament més rellevants.
     *
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function facturesPendents(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $facturesPendents = $this->facturaModel->pendentsCobrament($usuariId);

            return $this->jsonOk(['data' => $facturesPendents]);
        } catch (\Exception $e) {
            log_message('error', 'Error en dashboard facturesPendents: ' . $e->getMessage());

            return $this->jsonError('Error al carregar les factures pendents', 500);
        }
    }
    /**
     * Retorna les quotes amb períodes pendents de pagament.
     *
     * @return ResponseInterface Resposta JSON amb les dades de l'operació o detall d'error.
     */
    public function quotesProperes(): ResponseInterface
    {
        try {
            $usuariId = $this->usuariId();

            $quotes = $this->quotaModel->obtenirAmbPendents($usuariId);
            $quotesAmbPendents = array_values(array_filter(
                $quotes,
                static fn(array $quota): bool => (int) ($quota['periodes_pendents_count'] ?? 0) > 0
            ));

            return $this->jsonOk(['data' => $quotesAmbPendents]);
        } catch (\Exception $e) {
            log_message('error', 'Error en dashboard quotesProperes: ' . $e->getMessage());

            return $this->jsonError('Error al carregar les quotes properes', 500);
        }
    }
}
