<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

function userWithRole(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

function tokenFor(User $user): string
{
    return JWTAuth::fromUser($user);
}

test('registrasi publik selalu membuat pelanggan', function () {
    $this->post('/register', [
        'name' => 'Pelanggan Baru',
        'email' => 'pelanggan@example.com',
        'password' => 'password123',
        'role' => User::ROLE_SUPERADMIN,
    ])->assertRedirect(route('home'));

    $this->assertDatabaseHas('users', [
        'email' => 'pelanggan@example.com',
        'role' => User::ROLE_PELANGGAN,
    ]);
});

test('pelanggan tidak dapat mengakses panel admin', function () {
    $pelanggan = userWithRole(User::ROLE_PELANGGAN);

    $this->withCookie('token', tokenFor($pelanggan))
        ->get('/admin')
        ->assertForbidden();
});

test('admin tidak dapat mengakses pengelolaan admin', function () {
    $admin = userWithRole(User::ROLE_ADMIN);

    $this->withCookie('token', tokenFor($admin))
        ->get('/admin/users')
        ->assertForbidden();
});

test('superadmin dapat membuat akun admin', function () {
    $superadmin = userWithRole(User::ROLE_SUPERADMIN);

    $this->withCookie('token', tokenFor($superadmin))
        ->post('/admin/users', [
            'username' => 'Admin Apotek',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertRedirect(route('admin.users.index', ['role' => 'admin']));

    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    expect($admin->role)->toBe(User::ROLE_ADMIN)
        ->and(Hash::check('password123', $admin->password))->toBeTrue();
});

test('perintah create superadmin membuat akun superadmin pertama', function () {
    $this->artisan('app:create-superadmin', [
        'email' => 'superadmin@example.com',
        '--name' => 'Super Admin',
        '--password' => 'password123',
    ])->assertSuccessful();

    $this->assertDatabaseHas('users', [
        'email' => 'superadmin@example.com',
        'role' => User::ROLE_SUPERADMIN,
    ]);
});
