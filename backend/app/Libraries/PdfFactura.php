<?php

namespace App\Libraries;

class PdfFactura
{
    public function generar(array $factura, array $linies, ?array $client = null, ?array $usuari = null): string
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

        $subtotal = $this->money((float) ($factura['subtotal'] ?? 0));
        $ivaPercentatge = $this->number((float) ($factura['iva_percentatge'] ?? 0));
        $ivaImport = $this->money((float) ($factura['iva_import'] ?? 0));
        $irpfPercentatge = $this->number((float) ($factura['irpf_percentatge'] ?? 0));
        $irpfImport = $this->money((float) ($factura['irpf_import'] ?? 0));
        $total = $this->money((float) ($factura['total'] ?? 0));

        $liniesRows = '';
        foreach ($linies as $linia) {
            $liniesRows .= '<tr>'
                . '<td style="border:1px solid #d1d5db; padding:6px;">' . $this->escape($this->text($linia['descripcio'] ?? null, '-')) . '</td>'
                . '<td style="border:1px solid #d1d5db; padding:6px; text-align:right;">' . $this->number((float) ($linia['quantitat'] ?? 0), 3) . '</td>'
                . '<td style="border:1px solid #d1d5db; padding:6px; text-align:right;">' . $this->money((float) ($linia['preu_unitari'] ?? 0)) . '</td>'
                . '<td style="border:1px solid #d1d5db; padding:6px; text-align:right;">' . $this->number((float) ($linia['descompte'] ?? 0)) . ' %</td>'
                . '<td style="border:1px solid #d1d5db; padding:6px; text-align:right;">' . $this->money((float) ($linia['total_linia'] ?? 0)) . '</td>'
                . '</tr>';
        }

        if ($liniesRows === '') {
            $liniesRows = '<tr><td colspan="5" style="border:1px solid #d1d5db; padding:6px;">Sense línies</td></tr>';
        }

        return '
            <h1 style="font-size:20px;">Factura ' . $this->escape($numero) . '</h1>
            <table cellpadding="3" cellspacing="0" width="100%">
                <tr>
                    <td width="50%">
                        <strong>Emissor</strong><br>
                        ' . $this->escape($empresa) . '<br>
                        NIF: ' . $this->escape($empresaNif) . '<br>
                        ' . $this->escape($empresaAdreca) . '
                    </td>
                    <td width="50%">
                        <strong>Client</strong><br>
                        ' . $this->escape($clientNom) . '<br>
                        NIF: ' . $this->escape($clientNif) . '<br>
                        ' . $this->escape($clientAdreca) . '
                    </td>
                </tr>
            </table>

            <br>
            <table cellpadding="4" cellspacing="0" width="100%">
                <tr>
                    <td width="25%"><strong>Data emissió</strong></td>
                    <td width="25%">' . $this->escape($dataEmisio) . '</td>
                    <td width="25%"><strong>Data venciment</strong></td>
                    <td width="25%">' . $this->escape($dataVenciment) . '</td>
                </tr>
                <tr>
                    <td width="25%"><strong>Estat</strong></td>
                    <td width="25%">' . $this->escape($estat) . '</td>
                    <td width="25%"></td>
                    <td width="25%"></td>
                </tr>
            </table>

            <br>
            <table cellpadding="0" cellspacing="0" width="100%">
                <thead>
                    <tr style="font-weight:bold; background-color:#f3f4f6;">
                        <th style="border:1px solid #d1d5db; padding:6px;" width="44%">Descripció</th>
                        <th style="border:1px solid #d1d5db; padding:6px; text-align:right;" width="14%">Quantitat</th>
                        <th style="border:1px solid #d1d5db; padding:6px; text-align:right;" width="14%">Preu</th>
                        <th style="border:1px solid #d1d5db; padding:6px; text-align:right;" width="14%">Desc %</th>
                        <th style="border:1px solid #d1d5db; padding:6px; text-align:right;" width="14%">Total</th>
                    </tr>
                </thead>
                <tbody>' . $liniesRows . '</tbody>
            </table>

            <br>
            <table cellpadding="4" cellspacing="0" width="100%">
                <tr>
                    <td width="70%"></td>
                    <td width="30%">
                        <table cellpadding="4" cellspacing="0" width="100%">
                            <tr><td><strong>Subtotal</strong></td><td align="right">' . $subtotal . '</td></tr>
                            <tr><td><strong>IVA (' . $ivaPercentatge . ' %)</strong></td><td align="right">' . $ivaImport . '</td></tr>
                            <tr><td><strong>IRPF (' . $irpfPercentatge . ' %)</strong></td><td align="right">-' . $irpfImport . '</td></tr>
                            <tr><td><strong>Total</strong></td><td align="right"><strong>' . $total . '</strong></td></tr>
                        </table>
                    </td>
                </tr>
            </table>
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
        return $this->number($value) . ' EUR';
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
