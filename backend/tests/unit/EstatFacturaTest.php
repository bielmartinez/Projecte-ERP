<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class EstatFacturaTest extends CIUnitTestCase
{
    public function testTransicioEsborranyAEmesaEsValida(): void
    {
        $this->assertTrue($this->esTransicioValida('esborrany', 'emesa'));
    }

    public function testTransicioEmesaACancelLladaEsValida(): void
    {
        $this->assertTrue($this->esTransicioValida('emesa', 'cancel·lada'));
    }

    public function testTransicioParcialmentCobradaACancelLladaEsValida(): void
    {
        $this->assertTrue($this->esTransicioValida('parcialment_cobrada', 'cancel·lada'));
    }

    public function testTransicioEsborranyACobradaEsInvalida(): void
    {
        $this->assertFalse($this->esTransicioValida('esborrany', 'cobrada'));
    }

    public function testTransicioEsborranyACancelLladaEsInvalida(): void
    {
        $this->assertFalse($this->esTransicioValida('esborrany', 'cancel·lada'));
    }

    public function testTransicioCobradaAEmesaEsInvalida(): void
    {
        $this->assertFalse($this->esTransicioValida('cobrada', 'emesa'));
    }

    private function esTransicioValida(string $estatActual, string $nouEstat): bool
    {
        $transicionsPermeses = [
            'esborrany' => ['emesa'],
            'emesa' => ['cancel·lada'],
            'parcialment_cobrada' => ['cancel·lada'],
        ];

        return isset($transicionsPermeses[$estatActual])
            && in_array($nouEstat, $transicionsPermeses[$estatActual], true);
    }
}