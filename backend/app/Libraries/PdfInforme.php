<?php

namespace App\Libraries;

class PdfInforme
{
    public function generar(array $data, ?array $usuari = null): string
    {
        $pdf = new \TCPDF();
        $pdf->SetCreator('Projecte ERP');
        $pdf->SetAuthor($this->text($usuari['nom_empresa'] ?? null, 'Projecte ERP'));
        $pdf->SetTitle('Informe ' . ($data['periode']['etiqueta'] ?? ''));
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage();

        $html = $this->buildHtml($data, $usuari);
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('', 'S');
    }

    private function buildHtml(array $data, ?array $usuari): string
    {
        $empresa = $this->text($usuari['nom_empresa'] ?? null, trim(($usuari['nom'] ?? '') . ' ' . ($usuari['cognoms'] ?? '')) ?: '-');
        $empresaNif = $this->text($usuari['nif'] ?? null, '-');

        $periode = $data['periode'] ?? [];
        $etiqueta = $this->escape($periode['etiqueta'] ?? '-');
        $tipusPeriode = $periode['tipus'] ?? 'mensual';
        $dataInici = $this->escape($periode['data_inici'] ?? '-');
        $dataFi = $this->escape($periode['data_fi'] ?? '-');

        $titolTipus = match ($tipusPeriode) {
            'trimestral' => 'INFORME TRIMESTRAL',
            'anual' => 'INFORME ANUAL',
            default => 'INFORME MENSUAL',
        };

        $mov = $data['moviments'] ?? [];
        $fac = $data['factures'] ?? [];
        $fis = $data['fiscal'] ?? [];

        return '
            <div style="font-size:11px; color:#111827;">

                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td width="60%" style="font-size:20px; font-weight:bold;">' . $titolTipus . '</td>
                        <td width="40%" style="text-align:right; line-height:1.5;">
                            <strong>' . $this->escape($empresa) . '</strong><br>
                            NIF: ' . $this->escape($empresaNif) . '
                        </td>
                    </tr>
                </table>

                <div style="height:4px;"></div>

                <table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #d1d5db;">
                    <tr>
                        <td style="padding:7px; border-right:1px solid #d1d5db;"><strong>Període</strong></td>
                        <td style="padding:7px; border-right:1px solid #d1d5db;">' . $etiqueta . '</td>
                        <td style="padding:7px; border-right:1px solid #d1d5db;"><strong>Des de</strong></td>
                        <td style="padding:7px; border-right:1px solid #d1d5db;">' . $dataInici . '</td>
                        <td style="padding:7px; border-right:1px solid #d1d5db;"><strong>Fins a</strong></td>
                        <td style="padding:7px;">' . $dataFi . '</td>
                    </tr>
                </table>

                <div style="height:12px;"></div>

                <table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #d1d5db;">
                    <tr style="background-color:#f3f4f6;">
                        <td colspan="2" style="padding:8px; font-weight:bold; font-size:12px; border-bottom:1px solid #d1d5db;">Resum d\'activitat</td>
                    </tr>
                    <tr>
                        <td style="padding:8px; border-bottom:1px solid #d1d5db; border-right:1px solid #d1d5db;" width="60%">Ingressos totals</td>
                        <td style="padding:8px; text-align:right; border-bottom:1px solid #d1d5db;">' . $this->money((float) ($mov['ingressos'] ?? 0)) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:8px; border-bottom:1px solid #d1d5db; border-right:1px solid #d1d5db;">Despeses totals</td>
                        <td style="padding:8px; text-align:right; border-bottom:1px solid #d1d5db;">' . $this->money((float) ($mov['despeses'] ?? 0)) . '</td>
                    </tr>
                    <tr style="background-color:#f3f4f6;">
                        <td style="padding:9px; border-right:1px solid #d1d5db;"><strong>Benefici net</strong></td>
                        <td style="padding:9px; text-align:right;"><strong>' . $this->money((float) ($mov['benefici'] ?? 0)) . '</strong></td>
                    </tr>
                </table>

                <div style="height:12px;"></div>

                <table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #d1d5db;">
                    <tr style="background-color:#f3f4f6;">
                        <td colspan="2" style="padding:8px; font-weight:bold; font-size:12px; border-bottom:1px solid #d1d5db;">Facturació</td>
                    </tr>
                    <tr>
                        <td style="padding:8px; border-bottom:1px solid #d1d5db; border-right:1px solid #d1d5db;" width="60%">Factures emeses</td>
                        <td style="padding:8px; text-align:right; border-bottom:1px solid #d1d5db;">' . (int) ($fac['num_factures'] ?? 0) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:8px; border-bottom:1px solid #d1d5db; border-right:1px solid #d1d5db;">Base imposable</td>
                        <td style="padding:8px; text-align:right; border-bottom:1px solid #d1d5db;">' . $this->money((float) ($fac['base_imposable'] ?? 0)) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:8px; border-right:1px solid #d1d5db;">Total facturat</td>
                        <td style="padding:8px; text-align:right;">' . $this->money((float) ($fac['total_facturat'] ?? 0)) . '</td>
                    </tr>
                </table>

                <div style="height:12px;"></div>

                <table cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #d1d5db;">
                    <tr style="background-color:#f3f4f6;">
                        <td colspan="2" style="padding:8px; font-weight:bold; font-size:12px; border-bottom:1px solid #d1d5db;">Resum fiscal</td>
                    </tr>
                    <tr>
                        <td style="padding:8px; border-bottom:1px solid #d1d5db; border-right:1px solid #d1d5db;" width="60%">IVA repercutit (cobrat als clients)</td>
                        <td style="padding:8px; text-align:right; border-bottom:1px solid #d1d5db;">' . $this->money((float) ($fis['iva_repercutit'] ?? 0)) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:8px; border-bottom:1px solid #d1d5db; border-right:1px solid #d1d5db;">IVA suportat (pagat en despeses)</td>
                        <td style="padding:8px; text-align:right; border-bottom:1px solid #d1d5db;">' . $this->money((float) ($fis['iva_suportat'] ?? 0)) . '</td>
                    </tr>
                    <tr style="background-color:#f3f4f6;">
                        <td style="padding:9px; border-bottom:1px solid #d1d5db; border-right:1px solid #d1d5db;"><strong>Resultat IVA (a pagar a Hisenda)</strong></td>
                        <td style="padding:9px; text-align:right; border-bottom:1px solid #d1d5db;"><strong>' . $this->money((float) ($fis['resultat_iva'] ?? 0)) . '</strong></td>
                    </tr>
                    <tr>
                        <td style="padding:8px; border-right:1px solid #d1d5db;">IRPF retingut en factures</td>
                        <td style="padding:8px; text-align:right;">' . $this->money((float) ($fis['irpf_retingut'] ?? 0)) . '</td>
                    </tr>
                </table>

            </div>
        ';
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
