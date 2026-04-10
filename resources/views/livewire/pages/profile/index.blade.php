<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use function Livewire\Volt\{state, rules, mount};

state([
    'name'                  => '',
    'email'                 => '',
    'phone'                 => '',
    'current_password'      => '',
    'new_password'          => '',
    'new_password_confirmation' => '',
    'show_password_form'    => false,
]);

mount(function () {
    $user        = Auth::user();
    $this->name  = $user->name;
    $this->email = $user->email;
    $this->phone = $user->phone;
});

$updateProfile = function () {
    $user = Auth::user();

    $this->validate([
        'name'  => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email,' . $user->id],
        'phone' => ['nullable', 'string', 'max:20'],
    ]);

    $user->update([
        'name'  => $this->name,
        'email' => $this->email,
        'phone' => $this->phone,
    ]);

    session()->flash('success', 'Profile updated successfully.');
};

$updatePassword = function () {
    $user = Auth::user();

    $this->validate([
        'current_password' => ['required'],
        'new_password'     => ['required', 'string', 'confirmed', Rules\Password::defaults()],
    ]);

    if (!Hash::check($this->current_password, $user->password)) {
        $this->addError('current_password', 'Current password is incorrect.');
        return;
    }

    $user->update(['password' => Hash::make($this->new_password)]);

    $this->current_password           = '';
    $this->new_password               = '';
    $this->new_password_confirmation  = '';
    $this->show_password_form         = false;

    session()->flash('success', 'Password updated successfully.');
};

?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">Profile</h1>
        <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
            Manage your account information.
        </p>
    </div>

    {{-- Profile Info --}}
    <div class="rounded-xl p-8 mb-6" style="background-color:#FFFFFF;">
        <p class="font-manrope text-sm font-semibold mb-6" style="color:#283439;">
            Account Information
        </p>
        <form wire:submit="updateProfile" class="space-y-6">

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Full Name</label>
                <input wire:model="name" type="text"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('name') <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Email Address</label>
                <input wire:model="email" type="email"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('email') <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Phone Number</label>
                <input wire:model="phone" type="tel"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                    class="font-inter text-xs font-semibold text-white px-6 py-2.5
                        rounded-md transition-opacity hover:opacity-90"
                    style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Password --}}
    <div class="rounded-xl p-8" style="background-color:#FFFFFF;">
        <div class="flex items-center justify-between mb-6">
            <p class="font-manrope text-sm font-semibold" style="color:#283439;">Password</p>
            <button wire:click="$toggle('show_password_form')"
                class="font-inter text-xs font-medium px-3 py-1.5 rounded-md"
                style="background-color:#E7EFF3; color:#585E6C;">
                {{ $show_password_form ? 'Cancel' : 'Change Password' }}
            </button>
        </div>

        @if($show_password_form)
        <form wire:submit="updatePassword" class="space-y-6">
            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Current Password</label>
                <input wire:model="current_password" type="password"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('current_password') <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">New Password</label>
                <input wire:model="new_password" type="password"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
                @error('new_password') <p class="font-inter text-xs mt-1" style="color:#9F403D;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-inter text-xs font-medium uppercase tracking-widest mb-2"
                    style="color:#9BABB3;">Confirm New Password</label>
                <input wire:model="new_password_confirmation" type="password"
                    class="w-full border-0 border-b py-2 font-inter text-sm bg-transparent
                        focus:outline-none focus:ring-0"
                    style="border-color:#E7EFF3; color:#283439;" />
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                    class="font-inter text-xs font-semibold text-white px-6 py-2.5
                        rounded-md transition-opacity hover:opacity-90"
                    style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                    Update Password
                </button>
            </div>
        </form>
        @else
        <p class="font-inter text-sm" style="color:#9BABB3;">
            Last updated: {{ Auth::user()->updated_at->format('d M Y') }}
        </p>
        @endif
    </div>

</div>