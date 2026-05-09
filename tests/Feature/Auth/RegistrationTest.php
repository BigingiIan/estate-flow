<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');
    $response->assertStatus(200);
});

test('new users can register', function () {
    $component = Volt::test('pages/auth/register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('phone', '+254700000001')
        ->set('password', 'Password1!')
        ->set('password_confirmation', 'Password1!')
        ->call('register');

    $component->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'phone' => '+254700000001',
    ]);
});