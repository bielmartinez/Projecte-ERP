<?php

namespace App\Libraries;

use App\Models\RegistreVerifactuModel;

class VerifactuService
{
    private RegistreVerifactuModel $registreModel;

    /**
     * Inicialitza el model de registres Verifactu.
     */
    public function __construct()
    {
        $this->registreModel = new RegistreVerifactuModel();
    }

    /**
     * Genera i desa un registre Verifactu d'alta per a una factura.
     */
    public function generarRegistreAlta(array $factura, array $usuari): array
    {
        $usuariId = (int) ($usuari['id'] ?? 0);
        $facturaId = (int) ($factura['id'] ?? 0);

        if ($usuariId <= 0 || $facturaId <= 0) {
            throw new \RuntimeException('No s\'ha pogut determinar l\'usuari o la factura per generar el registre Verifactu.');
        }

        $nifEmisor = preg_replace('/\s+/', '', trim((string) ($usuari['nif'] ?? '')));
        if ($nifEmisor === '') {
            throw new \RuntimeException('L\'usuari ha de tenir el NIF informat per generar registres Verifactu.');
        }

        $ultimRegistre = $this->registreModel->obtenirUltimRegistre($usuariId);
        $dataHoraGeneracio = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Madrid'));

        $dadesFactura = json_encode($factura);
        if ($dadesFactura === false) {
            throw new \RuntimeException('No s\'ha pogut serialitzar el snapshot de la factura per a Verifactu.');
        }

        $dades = [
            'factura_id' => $facturaId,
            'usuari_id' => $usuariId,
            'tipus_registre' => 'alta',
            'subsanacio' => false,
            'nif_emisor' => strtoupper((string) $nifEmisor),
            'numero_factura' => trim((string) ($factura['numero_factura'] ?? '')),
            'data_emisio' => (string) ($factura['data_emisio'] ?? ''),
            'nom_rao_emisor' => $this->obtenirNomRaoEmisor($usuari),
            'tipus_factura' => 'F1',
            'quota_total' => $this->formatarImport($factura['iva_import'] ?? 0),
            'import_total' => $this->formatarImport($factura['total'] ?? 0),
            'data_hora_generacio' => $this->formatarDataHora($dataHoraGeneracio),
            'hash_anterior' => $ultimRegistre['hash_registre'] ?? null,
            'nif_emisor_anterior' => $ultimRegistre['nif_emisor'] ?? null,
            'numero_factura_anterior' => $ultimRegistre['numero_factura'] ?? null,
            'data_emisio_anterior' => $ultimRegistre['data_emisio'] ?? null,
            'dades_factura' => $dadesFactura,
        ];

        $dades['codi_qr'] = $this->generarUrlQR($dades);
        $dades['hash_registre'] = $this->calcularHashAlta($dades);

        $id = $this->registreModel->insert($dades, true);
        if ($id === false) {
            $errors = $this->registreModel->errors();
            log_message('error', 'Verifactu errors validació: ' . json_encode($errors));
            throw new \RuntimeException('No s\'ha pogut guardar el registre Verifactu d\'alta: ' . json_encode($errors));
        }

        $registre = $this->registreModel->find((int) $id);
        if (!is_array($registre)) {
            throw new \RuntimeException('No s\'ha pogut recuperar el registre Verifactu d\'alta creat.');
        }

        return $registre;
    }

    /**
     * Genera i desa un registre Verifactu d'anulació per a una factura.
     */
    public function generarRegistreAnulacio(array $factura, array $usuari): array
    {
        $usuariId = (int) ($usuari['id'] ?? 0);
        $facturaId = (int) ($factura['id'] ?? 0);

        if ($usuariId <= 0 || $facturaId <= 0) {
            throw new \RuntimeException('No s\'ha pogut determinar l\'usuari o la factura per generar el registre Verifactu.');
        }

        $nifEmisor = preg_replace('/\s+/', '', trim((string) ($usuari['nif'] ?? '')));
        if ($nifEmisor === '') {
            throw new \RuntimeException('L\'usuari ha de tenir el NIF informat per generar registres Verifactu.');
        }

        $ultimRegistre = $this->registreModel->obtenirUltimRegistre($usuariId);
        $dataHoraGeneracio = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Madrid'));

        $dadesFactura = json_encode($factura);
        if ($dadesFactura === false) {
            throw new \RuntimeException('No s\'ha pogut serialitzar el snapshot de la factura per a Verifactu.');
        }

        $tipusFactura = trim((string) ($factura['tipus_factura'] ?? 'F1'));
        if ($tipusFactura === '') {
            $tipusFactura = 'F1';
        }

        $dades = [
            'factura_id' => $facturaId,
            'usuari_id' => $usuariId,
            'tipus_registre' => 'anulacio',
            'subsanacio' => false,
            'nif_emisor' => strtoupper((string) $nifEmisor),
            'numero_factura' => trim((string) ($factura['numero_factura'] ?? '')),
            'data_emisio' => (string) ($factura['data_emisio'] ?? ''),
            'nom_rao_emisor' => $this->obtenirNomRaoEmisor($usuari),
            'tipus_factura' => $tipusFactura,
            'quota_total' => $this->formatarImport($factura['iva_import'] ?? 0),
            'import_total' => $this->formatarImport($factura['total'] ?? 0),
            'data_hora_generacio' => $this->formatarDataHora($dataHoraGeneracio),
            'hash_anterior' => $ultimRegistre['hash_registre'] ?? null,
            'nif_emisor_anterior' => $ultimRegistre['nif_emisor'] ?? null,
            'numero_factura_anterior' => $ultimRegistre['numero_factura'] ?? null,
            'data_emisio_anterior' => $ultimRegistre['data_emisio'] ?? null,
            'dades_factura' => $dadesFactura,
        ];

        $dades['codi_qr'] = $this->generarUrlQR($dades);
        $dades['hash_registre'] = $this->calcularHashAnulacio($dades);

        $id = $this->registreModel->insert($dades, true);
        if ($id === false) {
            $errors = $this->registreModel->errors();
            log_message('error', 'Verifactu errors validació: ' . json_encode($errors));
            throw new \RuntimeException('No s\'ha pogut guardar el registre Verifactu d\'anulació: ' . json_encode($errors));
        }

        $registre = $this->registreModel->find((int) $id);
        if (!is_array($registre)) {
            throw new \RuntimeException('No s\'ha pogut recuperar el registre Verifactu d\'anulació creat.');
        }

        return $registre;
    }

    /**
     * Recalcula i valida la cadena de hashos Verifactu d'un usuari.
     */
    public function validarCadena(int $usuariId): array
    {
        $registres = $this->registreModel->obtenirTotsRegistres($usuariId);
        $errors = [];

        $hashRegistreAnterior = null;

        foreach ($registres as $registre) {
            $dadesPerHash = [
                'nif_emisor' => (string) ($registre['nif_emisor'] ?? ''),
                'numero_factura' => (string) ($registre['numero_factura'] ?? ''),
                'data_emisio' => (string) ($registre['data_emisio'] ?? ''),
                'tipus_factura' => (string) ($registre['tipus_factura'] ?? 'F1'),
                'quota_total' => $registre['quota_total'] ?? 0,
                'import_total' => $registre['import_total'] ?? 0,
                'hash_anterior' => $registre['hash_anterior'] ?? null,
                'data_hora_generacio' => $registre['data_hora_generacio'] ?? '',
            ];

            $tipusRegistre = (string) ($registre['tipus_registre'] ?? '');
            if ($tipusRegistre === 'alta') {
                $hashRecalculat = $this->calcularHashAlta($dadesPerHash);
            } elseif ($tipusRegistre === 'anulacio') {
                $hashRecalculat = $this->calcularHashAnulacio($dadesPerHash);
            } else {
                $hashRecalculat = '';
                $errors[] = [
                    'registre_id' => (int) ($registre['id'] ?? 0),
                    'motiu' => 'Tipus de registre desconegut.',
                ];
            }

            if ($hashRecalculat !== '' && $hashRecalculat !== (string) ($registre['hash_registre'] ?? '')) {
                $errors[] = [
                    'registre_id' => (int) ($registre['id'] ?? 0),
                    'motiu' => 'Hash no coincideix',
                ];
            }

            $hashAnteriorRegistre = $registre['hash_anterior'] ?? null;
            if ($hashRegistreAnterior === null) {
                if ($hashAnteriorRegistre !== null && $hashAnteriorRegistre !== '') {
                    $errors[] = [
                        'registre_id' => (int) ($registre['id'] ?? 0),
                        'motiu' => 'Encadenament incorrecte: hash_anterior no coincideix',
                    ];
                }
            } elseif ((string) $hashAnteriorRegistre !== (string) $hashRegistreAnterior) {
                $errors[] = [
                    'registre_id' => (int) ($registre['id'] ?? 0),
                    'motiu' => 'Encadenament incorrecte: hash_anterior no coincideix',
                ];
            }

            $hashRegistreAnterior = (string) ($registre['hash_registre'] ?? '');
        }

        return [
            'valid' => $errors === [],
            'total_registres' => count($registres),
            'errors' => $errors,
        ];
    }

    /**
     * Calcula el hash SHA-256 d'un registre d'alta segons l'especificació AEAT.
     */
    private function calcularHashAlta(array $dades): string
    {
        $cadena = 'IDEmisorFactura=' . $dades['nif_emisor']
            . '&NumSerieFactura=' . $dades['numero_factura']
            . '&FechaExpedicionFactura=' . $this->formatarDataExpedicio($dades['data_emisio'])
            . '&TipoFactura=' . $dades['tipus_factura']
            . '&CuotaTotal=' . $this->formatarImport($dades['quota_total'])
            . '&ImporteTotal=' . $this->formatarImport($dades['import_total'])
            . '&Huella=' . ($dades['hash_anterior'] ?? '')
            . '&FechaHoraHusoGenRegistro=' . $this->formatarDataHora($dades['data_hora_generacio']);

        return strtoupper(hash('sha256', $cadena));
    }

    /**
     * Calcula el hash SHA-256 d'un registre d'anulació segons l'especificació AEAT.
     */
    private function calcularHashAnulacio(array $dades): string
    {
        $cadena = 'IDEmisorFactura=' . $dades['nif_emisor']
            . '&NumSerieFactura=' . $dades['numero_factura']
            . '&FechaExpedicionFactura=' . $this->formatarDataExpedicio($dades['data_emisio'])
            . '&Huella=' . ($dades['hash_anterior'] ?? '')
            . '&FechaHoraHusoGenRegistro=' . $this->formatarDataHora($dades['data_hora_generacio']);

        return strtoupper(hash('sha256', $cadena));
    }

    /**
     * Formata la data d'expedició al format requerit per l'AEAT (DD-MM-YYYY).
     */
    private function formatarDataExpedicio($data): string
    {
        return (new \DateTime((string) $data))->format('d-m-Y');
    }

    /**
     * Formata imports amb exactament 2 decimals i punt decimal.
     */
    private function formatarImport($valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }

    /**
     * Formata la data/hora en ISO 8601 amb el fus horari Europe/Madrid.
     */
    private function formatarDataHora($dataHora): string
    {
        $tzMadrid = new \DateTimeZone('Europe/Madrid');

        if (is_string($dataHora)) {
            $data = new \DateTimeImmutable($dataHora, $tzMadrid);
        } elseif ($dataHora instanceof \DateTimeImmutable) {
            $data = $dataHora;
        } elseif ($dataHora instanceof \DateTimeInterface) {
            $data = \DateTimeImmutable::createFromInterface($dataHora);
        } else {
            throw new \InvalidArgumentException('Format de data/hora no vàlid per a Verifactu.');
        }

        return $data->setTimezone($tzMadrid)->format('c');
    }

    /**
     * Genera la URL de validació QR en l'entorn de preproducció de l'AEAT.
     */
    private function generarUrlQR(array $dades): string
    {
        $params = [
            'nif' => $dades['nif_emisor'],
            'numserie' => $dades['numero_factura'],
            'fecha' => $this->formatarDataExpedicio($dades['data_emisio']),
            'importe' => $this->formatarImport($dades['import_total']),
        ];

        return 'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR?' . http_build_query($params);
    }

    /**
     * Obté el nom o raó social de l'emissor a partir de les dades d'usuari.
     */
    private function obtenirNomRaoEmisor(array $usuari): string
    {
        $nomEmpresa = trim((string) ($usuari['nom_empresa'] ?? ''));
        if ($nomEmpresa !== '') {
            return $nomEmpresa;
        }

        $nomComplet = trim((string) ($usuari['nom'] ?? '') . ' ' . (string) ($usuari['cognoms'] ?? ''));

        return $nomComplet !== '' ? $nomComplet : '-';
    }
}
