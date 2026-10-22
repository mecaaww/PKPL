<?php

use App\Models\PengumumanAdmin;
use App\Models\TargetOmzet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

if (! function_exists('userWithRole')) {
    function userWithRole(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }
}

if (! function_exists('tokenFor')) {
    function tokenFor(User $user): string
    {
        return JWTAuth::fromUser($user);
    }
}

test('1. pelanggan tidak dapat mengakses halaman pengaturan', function () {
    $pelanggan = userWithRole(User::ROLE_PELANGGAN);

    $this->withCookie('token', tokenFor($pelanggan))
        ->get('/admin/settings')
        ->assertForbidden();
});

test('2. admin tidak dapat mengakses halaman pengaturan', function () {
    $admin = userWithRole(User::ROLE_ADMIN);

    $this->withCookie('token', tokenFor($admin))
        ->get('/admin/settings')
        ->assertForbidden();
});

test('3. superadmin dapat membuka halaman pengaturan', function () {
    $superadmin = userWithRole(User::ROLE_SUPERADMIN);

    $this->withCookie('token', tokenFor($superadmin))
        ->get('/admin/settings')
        ->assertOk()
        ->assertSee('Pengaturan Superadmin');
});

test('4. target di bawah Rp1.000.000 ditolak', function () {
    $superadmin = userWithRole(User::ROLE_SUPERADMIN);

    $this->withCookie('token', tokenFor($superadmin))
        ->put('/admin/settings/target', ['target' => 999999])
        ->assertSessionHasErrors(['target' => 'Target omzet minimal Rp1.000.000.']);
});

test('5. target di atas Rp30.000.000 ditolak', function () {
    $superadmin = userWithRole(User::ROLE_SUPERADMIN);

    $this->withCookie('token', tokenFor($superadmin))
        ->put('/admin/settings/target', ['target' => 30000001])
        ->assertSessionHasErrors(['target' => 'Target omzet maksimal Rp30.000.000.']);
});

test('6. target antara Rp1.000.000 sampai Rp30.000.000 dapat disimpan', function () {
    $superadmin = userWithRole(User::ROLE_SUPERADMIN);

    $this->withCookie('token', tokenFor($superadmin))
        ->put('/admin/settings/target', ['target' => 15000000])
        ->assertRedirect(route('admin.settings.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('target_omzet', [
        'bulan' => Carbon::now()->month,
        'tahun' => Carbon::now()->year,
        'target' => 15000000,
    ]);
});

test('7. target pada bulan yang sama dapat diperbarui tanpa membuat data duplikat', function () {
    $superadmin = userWithRole(User::ROLE_SUPERADMIN);

    TargetOmzet::create([
        'bulan' => Carbon::now()->month,
        'tahun' => Carbon::now()->year,
        'target' => 10000000,
    ]);

    $this->withCookie('token', tokenFor($superadmin))
        ->put('/admin/settings/target', ['target' => 20000000])
        ->assertRedirect(route('admin.settings.index'));

    expect(TargetOmzet::where('bulan', Carbon::now()->month)->where('tahun', Carbon::now()->year)->count())->toBe(1);

    $this->assertDatabaseHas('target_omzet', [
        'bulan' => Carbon::now()->month,
        'tahun' => Carbon::now()->year,
        'target' => 20000000,
    ]);
});

test('8. pengumuman kurang dari 20 karakter ditolak', function () {
    $superadmin = userWithRole(User::ROLE_SUPERADMIN);

    $this->withCookie('token', tokenFor($superadmin))
        ->put('/admin/settings/announcement', ['konten' => 'Terlalu pendek'])
        ->assertSessionHasErrors(['konten' => 'Pengumuman minimal 20 karakter.']);
});

test('9. pengumuman lebih dari 300 karakter ditolak', function () {
    $superadmin = userWithRole(User::ROLE_SUPERADMIN);

    $this->withCookie('token', tokenFor($superadmin))
        ->put('/admin/settings/announcement', ['konten' => str_repeat('a', 301)])
        ->assertSessionHasErrors(['konten' => 'Pengumuman maksimal 300 karakter.']);
});

test('10. pengumuman berisi tag HTML ditolak', function () {
    $superadmin = userWithRole(User::ROLE_SUPERADMIN);

    $this->withCookie('token', tokenFor($superadmin))
        ->put('/admin/settings/announcement', ['konten' => '<b>Pengumuman penting</b> untuk seluruh admin apotek'])
        ->assertSessionHasErrors(['konten' => 'Pengumuman tidak boleh mengandung tag HTML.']);
});

test('11. pengumuman yang hanya berisi spasi ditolak', function () {
    $superadmin = userWithRole(User::ROLE_SUPERADMIN);

    $this->withCookie('token', tokenFor($superadmin))
        ->put('/admin/settings/announcement', ['konten' => '                              '])
        ->assertSessionHasErrors(['konten' => 'Pengumuman tidak boleh hanya berisi spasi.']);
});

test('12. pengumuman valid dapat disimpan dan diperbarui', function () {
    $superadmin = userWithRole(User::ROLE_SUPERADMIN);

    $this->withCookie('token', tokenFor($superadmin))
        ->put('/admin/settings/announcement', [
            'konten' => '   Pengumuman penting pertama untuk seluruh staf apotek.   ',
        ])
        ->assertRedirect(route('admin.settings.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('pengumuman_admin', [
        'konten' => 'Pengumuman penting pertama untuk seluruh staf apotek.',
    ]);

    // Update pengumuman
    $this->withCookie('token', tokenFor($superadmin))
        ->put('/admin/settings/announcement', [
            'konten' => 'Pengumuman kedua diperbarui untuk seluruh staf apotek.',
        ])
        ->assertRedirect(route('admin.settings.index'));

    expect(PengumumanAdmin::count())->toBe(1);

    $this->assertDatabaseHas('pengumuman_admin', [
        'konten' => 'Pengumuman kedua diperbarui untuk seluruh staf apotek.',
    ]);
});

test('13. admin dapat melihat target omzet dan pengumuman pada dashboard', function () {
    $admin = userWithRole(User::ROLE_ADMIN);

    TargetOmzet::create([
        'bulan' => Carbon::now()->month,
        'tahun' => Carbon::now()->year,
        'target' => 15000000,
    ]);

    PengumumanAdmin::create([
        'konten' => 'Pengumuman aktif yang tampil di dashboard admin.',
    ]);

    $this->withCookie('token', tokenFor($admin))
        ->get('/admin')
        ->assertOk()
        ->assertSee('Rp15.000.000')
        ->assertSee('Pengumuman aktif yang tampil di dashboard admin.');
});

test('14. admin tidak dapat mengubah target omzet atau pengumuman melalui request langsung', function () {
    $admin = userWithRole(User::ROLE_ADMIN);

    $this->withCookie('token', tokenFor($admin))
        ->put('/admin/settings/target', ['target' => 10000000])
        ->assertForbidden();

    $this->withCookie('token', tokenFor($admin))
        ->put('/admin/settings/announcement', ['konten' => 'Pengumuman tidak sah dari admin biasa'])
        ->assertForbidden();
});
