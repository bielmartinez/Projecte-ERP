<?php

use App\Libraries\VerifactuService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class VerifactuTest extends CIUnitTestCase
{
    public function testHashSha256VerifactuEsConsistent(): void
    {
        $servei = new VerifactuService();
        $dataHoraGeneracio = new DateTimeImmutable('2026-01-15 12:34:56', new DateTimeZone('Europe/Madrid'));

        $dades = [
            'nif_emisor' => '12345678A',
            'numero_factura' => 'F-001',
            'data_emisio' => '2026-01-15',
            'tipus_factura' => 'F1',
            'quota_total' => '21.00',
            'import_total' => '121.00',
            'hash_anterior' => '',
            'data_hora_generacio' => $dataHoraGeneracio,
        ];

        $hash1 = $this->invocarMetodePrivat($servei, 'calcularHashAlta', [$dades]);
        $hash2 = $this->invocarMetodePrivat($servei, 'calcularHashAlta', [$dades]);

        $cadenaEsperada = 'IDEmisorFactura=12345678A&NumSerieFactura=F-001&FechaExpedicionFactura=15-01-2026&TipoFactura=F1&CuotaTotal=21.00&ImporteTotal=121.00&Huella=&FechaHoraHusoGenRegistro=2026-01-15T12:34:56+01:00';
        $hashEsperat = strtoupper(hash('sha256', $cadenaEsperada));

        $this->assertSame($hashEsperat, $hash1);
        $this->assertSame($hash1, $hash2);
        $this->assertMatchesRegularExpression('/^[A-F0-9]{64}$/', $hash1);
    }

    private function invocarMetodePrivat(object $objecte, string $metode, array $arguments = []): mixed
    {
        $reflexio = new ReflectionMethod($objecte, $metode);
        $reflexio->setAccessible(true);

        return $reflexio->invokeArgs($objecte, $arguments);
    }
}