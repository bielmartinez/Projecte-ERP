<?php

use App\Models\FacturaModel;
use App\Models\LiniaFacturaModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FacturaModelTest extends CIUnitTestCase
{
    private FacturaModel $facturaModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facturaModel = new FacturaModel();
    }

    public function testValidacioRebutjaDadesIncompletes(): void
    {
        $validacioCorrecta = $this->facturaModel->validate([
            'usuari_id' => null,
            'client_id' => null,
            'serie' => '',
            'numero_factura' => '',
            'data_emisio' => '',
            'estat' => '',
            'subtotal' => null,
            'iva_percentatge' => null,
            'iva_import' => null,
            'irpf_percentatge' => null,
            'irpf_import' => null,
            'total' => null,
        ]);

        $this->assertFalse($validacioCorrecta);

        $errors = $this->facturaModel->errors();

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('usuari_id', $errors);
        $this->assertArrayHasKey('client_id', $errors);
        $this->assertArrayHasKey('numero_factura', $errors);
        $this->assertArrayHasKey('data_emisio', $errors);
        $this->assertArrayHasKey('estat', $errors);
        $this->assertArrayHasKey('total', $errors);
    }

    public function testCalculTotalsLinia(): void
    {
        $baseLinia = LiniaFacturaModel::calcularTotalLinia(2, 100, 21, 10);

        // El helper del model calcula la base amb descompte; l'IVA el sumem a part.
        $ivaLinia = round($baseLinia * 0.21, 2);
        $totalLinia = round($baseLinia + $ivaLinia, 2);

        $this->assertEquals(180.0, $baseLinia, '', 0.01);
        $this->assertEquals(37.8, $ivaLinia, '', 0.01);
        $this->assertEquals(217.8, $totalLinia, '', 0.01);
    }

    public function testGeneraNumeroFacturaInicial(): void
    {
        $model = $this->crearMockFacturaModel(null);

        $this->assertSame('F-001', $model->generarNumeroFactura(1));
    }

    public function testGeneraNumeroFacturaSeguent(): void
    {
        $model = $this->crearMockFacturaModel([
            'numero_factura' => 'F-005',
        ]);

        $this->assertSame('F-006', $model->generarNumeroFactura(1));
    }

    private function crearMockFacturaModel(?array $ultimaFactura): FacturaModel
    {
        return new class ($ultimaFactura) extends FacturaModel {
            private ?array $ultimaFactura;

            public function __construct(?array $ultimaFactura)
            {
                $this->ultimaFactura = $ultimaFactura;
            }

            public function where($key, $value = null, ?bool $escape = null): self
            {
                return $this;
            }

            public function orderBy(string $orderBy, string $direction = '', ?bool $escape = null): self
            {
                return $this;
            }

            public function first(): ?array
            {
                return $this->ultimaFactura;
            }
        };
    }
}