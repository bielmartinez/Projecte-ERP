<?php

namespace App\Controllers;

use App\Models\FacturaModel;
use App\Models\MovimentModel;
use App\Models\UsuariModel;
use App\Libraries\PdfInforme;
use CodeIgniter\HTTP\ResponseInterface;

class InformeController extends BaseController
{
    protected MovimentModel $movimentModel;
    protected FacturaModel $facturaModel;

    public function __construct()
    {
        $this->movimentModel = new MovimentModel();
        $this->facturaModel = new FacturaModel();
    }

    /**
     * GET /informes/mensual/:any/:mes
     */
    public function mensual(int $any, int $mes): ResponseInterface
    {
        $usuariId = user_id();
        if (!$usuariId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'No autenticat',
            ]);
        }

        if ($mes < 1 || $mes > 12 || $any < 2000 || $any > 2100) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Període no vàlid',
            ]);
        }

        $mesStr = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
        $dataInici = "{$any}-{$mesStr}-01";
        $dataFi = date('Y-m-t', strtotime($dataInici));
        $etiqueta = "{$mesStr}/{$any}";

        return $this->generarInforme($usuariId, $dataInici, $dataFi, 'mensual', $etiqueta);
    }

    /**
     * GET /informes/trimestral/:any/:trimestre
     */
    public function trimestral(int $any, int $trimestre): ResponseInterface
    {
        $usuariId = user_id();
        if (!$usuariId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'No autenticat',
            ]);
        }

        if ($trimestre < 1 || $trimestre > 4 || $any < 2000 || $any > 2100) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Període no vàlid',
            ]);
        }

        $mesInici = (($trimestre - 1) * 3) + 1;
        $mesFi = $mesInici + 2;
        $mesIniciStr = str_pad((string) $mesInici, 2, '0', STR_PAD_LEFT);
        $mesFiStr = str_pad((string) $mesFi, 2, '0', STR_PAD_LEFT);
        $dataInici = "{$any}-{$mesIniciStr}-01";
        $dataFi = date('Y-m-t', strtotime("{$any}-{$mesFiStr}-01"));
        $etiqueta = "T{$trimestre}/{$any}";

        return $this->generarInforme($usuariId, $dataInici, $dataFi, 'trimestral', $etiqueta);
    }

    /**
     * GET /informes/anual/:any
     */
    public function anual(int $any): ResponseInterface
    {
        $usuariId = user_id();
        if (!$usuariId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'No autenticat',
            ]);
        }

        if ($any < 2000 || $any > 2100) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Període no vàlid',
            ]);
        }

        $dataInici = "{$any}-01-01";
        $dataFi = "{$any}-12-31";
        $etiqueta = (string) $any;

        return $this->generarInforme($usuariId, $dataInici, $dataFi, 'anual', $etiqueta);
    }

    /**
     * GET /informes/pdf/:tipus/:periode
     * Exemples: /informes/pdf/mensual/2026-04, /informes/pdf/trimestral/2026-2, /informes/pdf/anual/2026
     */
    public function pdf(string $tipus, string $periode): ResponseInterface
    {
        $usuariId = user_id();
        if (!$usuariId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'No autenticat',
            ]);
        }

        // Calcular dates segons el tipus i període
        switch ($tipus) {
            case 'mensual':
                $parts = explode('-', $periode);
                if (count($parts) !== 2) {
                    return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Format: YYYY-MM']);
                }
                $any = (int) $parts[0];
                $mes = (int) $parts[1];
                if ($mes < 1 || $mes > 12 || $any < 2000 || $any > 2100) {
                    return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Període no vàlid']);
                }
                $mesStr = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
                $dataInici = "{$any}-{$mesStr}-01";
                $dataFi = date('Y-m-t', strtotime($dataInici));
                $etiqueta = "{$mesStr}/{$any}";
                break;

            case 'trimestral':
                $parts = explode('-', $periode);
                if (count($parts) !== 2) {
                    return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Format: YYYY-T']);
                }
                $any = (int) $parts[0];
                $trimestre = (int) $parts[1];
                if ($trimestre < 1 || $trimestre > 4 || $any < 2000 || $any > 2100) {
                    return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Període no vàlid']);
                }
                $mesInici = (($trimestre - 1) * 3) + 1;
                $mesFi = $mesInici + 2;
                $dataInici = "{$any}-" . str_pad((string) $mesInici, 2, '0', STR_PAD_LEFT) . "-01";
                $dataFi = date('Y-m-t', strtotime("{$any}-" . str_pad((string) $mesFi, 2, '0', STR_PAD_LEFT) . "-01"));
                $etiqueta = "T{$trimestre}/{$any}";
                break;

            case 'anual':
                $any = (int) $periode;
                if ($any < 2000 || $any > 2100) {
                    return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Període no vàlid']);
                }
                $dataInici = "{$any}-01-01";
                $dataFi = "{$any}-12-31";
                $etiqueta = (string) $any;
                break;

            default:
                return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Tipus ha de ser: mensual, trimestral o anual']);
        }

        try {
            $resumMoviments = $this->movimentModel->resumPerPeriode($usuariId, $dataInici, $dataFi);
            $resumFactures = $this->facturaModel->resumFiscalPeriode($usuariId, $dataInici, $dataFi);
            $ivaSuportat = $this->movimentModel->ivaSuportatPeriode($usuariId, $dataInici, $dataFi);

            $ingressos = $resumMoviments['ingressos'];
            $despeses = $resumMoviments['despeses'];
            $ivaRepercutit = $resumFactures['iva_repercutit'];

            $informeData = [
                'periode' => [
                    'tipus' => $tipus,
                    'etiqueta' => $etiqueta,
                    'data_inici' => $dataInici,
                    'data_fi' => $dataFi,
                ],
                'moviments' => [
                    'ingressos' => $ingressos,
                    'despeses' => $despeses,
                    'benefici' => round($ingressos - $despeses, 2),
                ],
                'factures' => [
                    'num_factures' => $resumFactures['num_factures'],
                    'base_imposable' => $resumFactures['base_imposable'],
                    'total_facturat' => $resumFactures['total_facturat'],
                ],
                'fiscal' => [
                    'iva_repercutit' => $ivaRepercutit,
                    'iva_suportat' => $ivaSuportat,
                    'resultat_iva' => round($ivaRepercutit - $ivaSuportat, 2),
                    'irpf_retingut' => $resumFactures['irpf_retingut'],
                ],
            ];

            $usuariModel = new UsuariModel();
            $usuari = $usuariModel->find($usuariId);

            $pdfLib = new PdfInforme();
            $pdfContent = $pdfLib->generar($informeData, $usuari);

            $nomFitxer = "informe_{$tipus}_{$periode}.pdf";

            return $this->response
                ->setStatusCode(200)
                ->setContentType('application/pdf')
                ->setHeader('Content-Disposition', "attachment; filename=\"{$nomFitxer}\"")
                ->setBody($pdfContent);
        } catch (\Exception $e) {
            log_message('error', "Error en informe PDF {$tipus}: " . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al generar el PDF de l\'informe',
            ]);
        }
    }

    /**
     * Lògica compartida per generar l'informe de qualsevol període.
     */
    private function generarInforme(int $usuariId, string $dataInici, string $dataFi, string $tipus, string $etiqueta): ResponseInterface
    {
        try {
            $resumMoviments = $this->movimentModel->resumPerPeriode($usuariId, $dataInici, $dataFi);
            $resumFactures = $this->facturaModel->resumFiscalPeriode($usuariId, $dataInici, $dataFi);
            $ivaSuportat = $this->movimentModel->ivaSuportatPeriode($usuariId, $dataInici, $dataFi);

            $ingressos = $resumMoviments['ingressos'];
            $despeses = $resumMoviments['despeses'];
            $benefici = round($ingressos - $despeses, 2);

            $ivaRepercutit = $resumFactures['iva_repercutit'];
            $resultatIva = round($ivaRepercutit - $ivaSuportat, 2);

            return $this->response->setJSON([
                'status' => 'ok',
                'data' => [
                    'periode' => [
                        'tipus' => $tipus,
                        'etiqueta' => $etiqueta,
                        'data_inici' => $dataInici,
                        'data_fi' => $dataFi,
                    ],
                    'moviments' => [
                        'ingressos' => $ingressos,
                        'despeses' => $despeses,
                        'benefici' => $benefici,
                    ],
                    'factures' => [
                        'num_factures' => $resumFactures['num_factures'],
                        'base_imposable' => $resumFactures['base_imposable'],
                        'total_facturat' => $resumFactures['total_facturat'],
                    ],
                    'fiscal' => [
                        'iva_repercutit' => $ivaRepercutit,
                        'iva_suportat' => $ivaSuportat,
                        'resultat_iva' => $resultatIva,
                        'irpf_retingut' => $resumFactures['irpf_retingut'],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', "Error en informe {$tipus}: " . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Error al generar l\'informe',
            ]);
        }
    }
}
