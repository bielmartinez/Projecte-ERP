<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AuthTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testEndpointProtegitSenseTokenRetorna401(): void
    {
        $resposta = $this->call('GET', '/factures');

        $resposta->assertStatus(401);
        $resposta->assertJSONFragment(['status' => 'error']);
    }
}