<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/profile');
    $response->assertStatus(200);
    $response->assertSee('Profile');
    $response->assertSee('Account Information');
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('pages/profile/index')
        ->set('name', 'Updated Name')
        ->set('email', 'updated@example.com')
        ->set('phone', '+254700000099')
        ->call('updateProfile');

    $user->refresh();

    $this->assertSame('Updated Name', $user->name);
    $this->assertSame('updated@example.com', $user->email);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('pages/profile/index')
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->call('updateProfile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can update their password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('pages/profile/index')
        ->set('show_password_form', true)
        ->set('current_password', 'password')
        ->set('new_password', 'NewPassword1!')
        ->set('new_password_confirmation', 'NewPassword1!')
        ->call('updatePassword');

    $this->assertTrue(
        \Illuminate\Support\Facades\Hash::check('NewPassword1!', $user->refresh()->password)
    );
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Volt::test('pages/profile/index')
        ->set('show_password_form', true)
        ->set('current_password', 'wrong-password')
        ->set('new_password', 'NewPassword1!')
        ->set('new_password_confirmation', 'NewPassword1!')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);
});