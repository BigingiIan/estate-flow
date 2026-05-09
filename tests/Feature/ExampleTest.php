<?php

use App\Models\User;

it('redirects guests to login', function () {
    $response = $this->get('/');
    $response->assertRedirect('/login');
});

it('authenticated users reach dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
});