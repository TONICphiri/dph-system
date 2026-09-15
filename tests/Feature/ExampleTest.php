<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_serves_a_public_homepage_and_guards_the_dashboard(): void
    {
        // Public landing page: no login needed.
        $this->get('/')->assertStatus(200);

        // Inside the system: guests are sent to sign in.
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
