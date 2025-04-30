<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_requires_name_to_register()
    {
        $response = $this->post('/register', [
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function it_requires_email_to_register()
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function it_requires_password_to_register()
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'user@example.com',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function it_requires_password_to_be_at_least_8_characters()
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'user@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function it_requires_password_to_match_confirmation()
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function it_registers_the_user_and_redirects_to_the_login_page()
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
        ]);
    }

   
    
}
