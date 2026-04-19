<?php

namespace App\Libraries;

class PdfFactura
{
    public function generar(array $factura, array $linies, ?array $client = null, ?array $usuari = null, ?string $urlQR = null): string
    {
        $pdf = new \TCPDF();
        $pdf->SetCreator('Projecte ERP');
        $pdf->SetAuthor($this->text($usuari['nom_empresa'] ?? null, 'Projecte ERP'));
        $pdf->SetTitle('Factura ' . $this->text($factura['numero_factura'] ?? null, ''));
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage();
        $html = $this->buildHtml($factura, $linies, $client, $usuari);
        $pdf->writeHTML($html, true, false, true, false, '');

        if ($urlQR !== null && $urlQR !== '') {
            $yQR = $pdf->GetY();
            $xQR = 15;

            $pdf->write2DBarcode($urlQR, 'QRCODE,H', $xQR, $yQR, 30, 30, [
                'border' => false,
                'padding' => 1,
                'fgcolor' => [0, 0, 0],
                'bgcolor' => [255, 255, 255],
            ]);

            $pdf->SetFont('helvetica', '', 6);
            $pdf->SetXY($xQR, $yQR + 30);
            $pdf->Cell(30, 4, 'Factura verificable', 0, 0, 'C');
            $pdf->SetXY($xQR, $yQR + 33);
            $pdf->Cell(30, 4, 'VERI*FACTU', 0, 0, 'C');
        }


        return $pdf->Output('', 'S');
    }

    private function buildHtml(array $factura, array $linies, ?array $client, ?array $usuari): string
    {
        $empresa = $this->text($usuari['nom_empresa'] ?? null, trim(($usuari['nom'] ?? '') . ' ' . ($usuari['cognoms'] ?? '')) ?: '-');
        $empresaNif = $this->text($usuari['nif'] ?? null, '-');
        $empresaAdreca = $this->formatAddress($usuari);

        $clientNom = $this->text($client['nom_empresa'] ?? null, trim(($client['nom'] ?? '') . ' ' . ($client['cognoms'] ?? '')) ?: '-');
        $clientNif = $this->text($client['nif'] ?? null, '-');
        $clientAdreca = $this->formatAddress($client);

        $numero = $this->text($factura['numero_factura'] ?? null, '-');
        $dataEmisio = $this->text($factura['data_emisio'] ?? null, '-');
        $dataVenciment = $this->text($factura['data_venciment'] ?? null, '-');
        $estat = $this->text($factura['estat'] ?? null, '-');
        $observacions = trim((string) ($factura['observacions'] ?? ''));

        $subtotal = $this->money((float) ($factura['subtotal'] ?? 0));
        $ivaPercentatge = $this->number((float) ($factura['iva_percentatge'] ?? 0));
        $ivaImport = $this->money((float) ($factura['iva_import'] ?? 0));
        $irpfPercentatge = $this->number((float) ($factura['irpf_percentatge'] ?? 0));
        $irpfImport = $this->money((float) ($factura['irpf_import'] ?? 0));
        $total = $this->money((float) ($factura['total'] ?? 0));

        $liniesRows = '';
        foreach ($linies as $linia) {
            $liniesRows .= '<tr>'
                . '<td style="border:1px solid #d1d5db; padding:8px; vertical-align:middle;" width="36%">' . $this->escape($this->text($linia['descripcio'] ?? null, '-')) . '</td>'
                . '<td style="border:1px solid #d1d5db; padding:8px; text-align:right; vertical-align:middle;" width="12%">' . $this->number((float) ($linia['quantitat'] ?? 0), 3) . '</td>'
                . '<td style="border:1px solid #d1d5db; padding:8px; text-align:right; vertical-align:middle;" width="14%">' . $this->money((float) ($linia['preu_unitari'] ?? 0)) . '</td>'
                . '<td style="border:1px solid #d1d5db; padding:8px; text-align:right; vertical-align:middle;" width="12%">' . $this->number((float) ($linia['iva_percentatge'] ?? 0)) . ' %</td>'
                . '<td style="border:1px solid #d1d5db; padding:8px; text-align:right; vertical-align:middle;" width="12%">' . $this->number((float) ($linia['descompte'] ?? 0)) . ' %</td>'
                . '<td style="border:1px solid #d1d5db; padding:8px; text-align:right; vertical-align:middle;" width="14%">' . $this->money((float) ($linia['total_linia'] ?? 0)) . '</td>'
                . '</tr>';
        }

        if ($liniesRows === '') {
            $liniesRows = '<tr><td colspan="6" style="border:1px solid #d1d5db; padding:8px; text-align:center;">Sense línies</td></tr>';
        }

        return '
            <div style="font-size:11px; color:#111827;">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td width="62%" style="font-size:22px; font-weight:bold;">FACTURA</td>
                        <td width="38%" style="text-align:right;">
                            <table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #d1d5db;">
                                <tr>
                                    <td style="padding:7px; border-right:1px solid #d1d5db; border-bottom:1px solid #d1d5db;"><strong>Núm. factura</strong></td>
                                    <td style="padding:7px; text-align:right; border-bottom:1px solid #d1d5db;">' . $this->escape($numero) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:7px; border-right:1px solid #d1d5db;"><strong>Data emissió</strong></td>
                                    <td style="padding:7px; text-align:right;">' . $this->escape($dataEmisio) . '</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <div style="height:10px;"></div>

                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td width="49%" style="border:1px solid #d1d5db; padding:9px; vertical-align:top; line-height:1.45;">
                            <strong style="font-size:12px;">Emissor</strong><br>
                            ' . $this->escape($empresa) . '<br>
                            NIF: ' . $this->escape($empresaNif) . '<br>
                            ' . $this->escape($empresaAdreca) . '
                        </td>
                        <td width="2%"></td>
                        <td width="49%" style="border:1px solid #d1d5db; padding:9px; vertical-align:top; line-height:1.45;">
                            <strong style="font-size:12px;">Client</strong><br>
                            ' . $this->escape($clientNom) . '<br>
                            NIF: ' . $this->escape($clientNif) . '<br>
                            ' . $this->escape($clientAdreca) . '
                        </td>
                    </tr>
                </table>

                <div style="height:10px;"></div>

                <table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #d1d5db;">
                    <tr>
                        <td width="25%" style="padding:7px; border-right:1px solid #d1d5db;"><strong>Data venciment</strong></td>
                        <td width="25%" style="padding:7px; border-right:1px solid #d1d5db;">' . $this->escape($dataVenciment) . '</td>
                        <td width="25%" style="padding:7px; border-right:1px solid #d1d5db;"><strong>Estat</strong></td>
                        <td width="25%" style="padding:7px;">' . $this->escape($estat) . '</td>
                    </tr>
                </table>

                <div style="height:10px;"></div>

                <table cellpadding="0" cellspacing="0" width="100%">
                    <thead>
                        <tr style="font-weight:bold; background-color:#f3f4f6;">
                            <th style="border:1px solid #d1d5db; padding:8px; text-align:left;" width="36%">Concepte</th>
                            <th style="border:1px solid #d1d5db; padding:8px; text-align:right;" width="12%">Quantitat</th>
                            <th style="border:1px solid #d1d5db; padding:8px; text-align:right;" width="14%">Preu unit.</th>
                            <th style="border:1px solid #d1d5db; padding:8px; text-align:right;" width="12%">IVA %</th>
                            <th style="border:1px solid #d1d5db; padding:8px; text-align:right;" width="12%">Dto. %</th>
                            <th style="border:1px solid #d1d5db; padding:8px; text-align:right;" width="14%">Base imp.</th>
                        </tr>
                    </thead>
                    <tbody>' . $liniesRows . '</tbody>
                </table>

                <div style="height:10px;"></div>

                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td width="55%"></td>
                        <td width="45%">
                            <table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #d1d5db;">
                                <tr style="background-color:#f3f4f6;">
                                    <td style="padding:8px; border-bottom:1px solid #d1d5db;"><strong>Resum impostos</strong></td>
                                    <td style="padding:8px; text-align:right; border-bottom:1px solid #d1d5db;"><strong>Import</strong></td>
                                </tr>
                                <tr>
                                    <td style="padding:8px; border-bottom:1px solid #d1d5db;"><strong>Base imposable</strong></td>
                                    <td style="padding:8px; text-align:right; border-bottom:1px solid #d1d5db; white-space:nowrap;">' . $subtotal . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px; border-bottom:1px solid #d1d5db;"><strong>Tipus IVA</strong></td>
                                    <td style="padding:8px; text-align:right; border-bottom:1px solid #d1d5db; white-space:nowrap;">' . $ivaPercentatge . ' %</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px; border-bottom:1px solid #d1d5db;"><strong>Quota IVA</strong></td>
                                    <td style="padding:8px; text-align:right; border-bottom:1px solid #d1d5db; white-space:nowrap;">' . $ivaImport . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px; border-bottom:1px solid #d1d5db;"><strong>Retenció IRPF (' . $irpfPercentatge . ' %)</strong></td>
                                    <td style="padding:8px; text-align:right; border-bottom:1px solid #d1d5db; white-space:nowrap;">-' . $irpfImport . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:9px; background-color:#f3f4f6;"><strong>Total factura</strong></td>
                                    <td style="padding:9px; text-align:right; white-space:nowrap; background-color:#f3f4f6;"><strong>' . $total . '</strong></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                ' . ($observacions !== '' ? '
                <div style="height:10px;"></div>
                <table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #d1d5db;">
                    <tr>
                        <td style="padding:8px; background-color:#f3f4f6;"><strong>Observacions</strong></td>
                    </tr>
                    <tr>
                        <td style="padding:8px; line-height:1.4;">' . nl2br($this->escape($observacions)) . '</td>
                    </tr>
                </table>' : '') . '
            </div>
        ';
    }

    private function formatAddress(?array $persona): string
    {
        if (!$persona) {
            return '-';
        }

        $parts = array_filter([
            trim((string) ($persona['adreca'] ?? '')),
            trim((string) ($persona['codi_postal'] ?? '')),
            trim((string) ($persona['poblacio'] ?? '')),
            trim((string) ($persona['provincia'] ?? '')),
            trim((string) ($persona['pais'] ?? '')),
        ], static fn ($part) => $part !== '');

        if ($parts === []) {
            return '-';
        }

        return implode(', ', $parts);
    }

    private function text(mixed $value, string $fallback): string
    {
        $text = trim((string) ($value ?? ''));
        return $text !== '' ? $text : $fallback;
    }

    private function number(float $value, int $decimals = 2): string
    {
        return number_format($value, $decimals, ',', '.');
    }

    private function money(float $value): string
    {
        return $this->number($value) . ' €';
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
