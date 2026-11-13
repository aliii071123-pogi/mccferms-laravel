<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_homepage_redirects_to_microsoft_sign_in(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
        $this->get('/login')
            ->assertOk()
            ->assertSee('MCC FERMS')
            ->assertSee('mcc-campus-background.jpg')
            ->assertSee('login-content-enter', false)
            ->assertSee('prefers-reduced-motion', false);
    }
}
