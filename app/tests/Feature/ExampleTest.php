<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_raiz_redirige_al_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
