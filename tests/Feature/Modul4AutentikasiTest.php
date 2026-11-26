<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

final class Modul4AutentikasiTest extends TestCase
{
    use RefreshDatabase;

    private const SANDI_AWAL = 'SandiUji123!';

    private const SANDI_BARU = 'SandiBaru456!';

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Pengujian Modul 4 memerlukan SQLite :memory:.');
        }
    }

    public function test_login_superadmin_dengan_kredensial_benar(): void
    {
        // Arrange
        $user = $this->buatSuperadmin();

        // Act
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => self::SANDI_AWAL,
        ]);

        // Assert
        $response->assertRedirectToRoute('admin.dashboard')->assertCookie('token');
        $this->assertAuthenticatedAs($user, 'api');
    }

    public function test_login_menolak_kata_sandi_yang_salah(): void
    {
        // Arrange
        $user = $this->buatSuperadmin();

        // Act
        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'SandiTidakCocok789!',
        ]);

        // Assert
        $response->assertRedirectToRoute('login')
            ->assertSessionHas('error', 'Email atau password salah')
            ->assertCookieMissing('token');
        $this->assertGuest('api');
    }

    #[DataProvider('peranAkunBaru')]
    public function test_register_superadmin_membuat_akun_baru_dengan_hash_kata_sandi(string $peran): void
    {
        // Arrange
        $superadmin = $this->buatSuperadmin();
        $data = [
            'username' => 'Pengguna Uji Modul 4',
            'email' => $peran.'.modul4@example.test',
            'password' => self::SANDI_AWAL,
            'password_confirmation' => self::SANDI_AWAL,
            'role' => $peran,
        ];

        // Act
        $response = $this->withCookie('token', JWTAuth::fromUser($superadmin))
            ->post(route('admin.users.store'), $data);

        // Assert
        $response->assertRedirectToRoute('admin.users.index', ['role' => $peran])
            ->assertSessionHas('success', "Akun {$peran} berhasil dibuat.");
        $this->assertDatabaseHas('users', [
            'username' => $data['username'],
            'email' => $data['email'],
            'role' => $peran,
        ]);
        $user = User::where('email', $data['email'])->sole();
        $this->assertTrue(Hash::check(self::SANDI_AWAL, $user->password));
    }

    public static function peranAkunBaru(): iterable
    {
        yield 'Admin' => [User::ROLE_ADMIN];
        yield 'Pelanggan' => [User::ROLE_PELANGGAN];
    }

    public function test_register_menolak_alamat_email_yang_sudah_dipakai(): void
    {
        // Arrange
        $superadmin = $this->buatSuperadmin();
        $form = route('admin.users.create', ['role' => User::ROLE_ADMIN]);

        // Act
        $response = $this->withCookie('token', JWTAuth::fromUser($superadmin))
            ->from($form)->post(route('admin.users.store'), [
                'username' => 'Pengguna Duplikat Uji',
                'email' => $superadmin->email,
                'password' => self::SANDI_BARU,
                'password_confirmation' => self::SANDI_BARU,
                'role' => User::ROLE_ADMIN,
            ]);

        // Assert
        $response->assertRedirect($form)
            ->assertSessionHasErrors(['email' => 'Email sudah digunakan.']);
        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check(self::SANDI_AWAL, $superadmin->fresh()->password));
    }

    public function test_lupa_dan_reset_password_memperbarui_kata_sandi(): void
    {
        // Arrange
        Notification::fake();
        $user = $this->buatSuperadmin();

        // Act
        $permintaan = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);

        // Assert
        $permintaan->assertRedirectToRoute('password.request')
            ->assertSessionHas('status', 'Tautan untuk mengatur ulang kata sandi telah dikirim ke email Anda.');
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        $token = Notification::sent($user, ResetPassword::class)->sole()->token;
        $permintaan->assertSessionHas('reset_link', route('password.reset', ['token' => $token, 'email' => $user->email]));

        // Arrange
        $formulir = [
            'token' => $token,
            'email' => $user->email,
            'password' => self::SANDI_BARU,
            'password_confirmation' => self::SANDI_BARU,
        ];

        // Act
        $reset = $this->post('/reset-password', $formulir);

        // Assert
        $reset->assertRedirectToRoute('login')
            ->assertSessionHas('success', 'Kata sandi berhasil diperbarui! Silakan masuk dengan kata sandi baru Anda.');
        $this->assertTrue(Hash::check(self::SANDI_BARU, $user->fresh()->password));
        $this->assertFalse(Password::broker()->tokenExists($user, $token));
    }

    public function test_lupa_password_menolak_email_tidak_terdaftar(): void
    {
        // Arrange
        Notification::fake();
        $email = 'belum.terdaftar.modul4@example.test';

        // Act
        $response = $this->from('/forgot-password')->post('/forgot-password', ['email' => $email]);

        // Assert
        $response->assertRedirectToRoute('password.request')
            ->assertSessionHasErrors(['email' => 'Kami tidak dapat menemukan akun dengan email tersebut'])
            ->assertSessionMissing('reset_link');
        Notification::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $email]);
    }

    public function test_reset_password_menolak_token_yang_salah(): void
    {
        // Arrange
        $user = $this->buatSuperadmin();
        $tokenValid = Password::broker()->createToken($user);
        $formUrl = route('password.reset', ['token' => $tokenValid, 'email' => $user->email]);

        // Act
        $response = $this->from($formUrl)->post('/reset-password', [
            'token' => 'token-uji-yang-salah',
            'email' => $user->email,
            'password' => self::SANDI_BARU,
            'password_confirmation' => self::SANDI_BARU,
        ]);

        // Assert
        $response->assertRedirect($formUrl)
            ->assertSessionHasErrors(['email' => 'Token reset kata sandi tidak valid atau sudah kedaluwarsa.']);
        $this->assertTrue(Hash::check(self::SANDI_AWAL, $user->fresh()->password));
        $this->assertTrue(Password::broker()->tokenExists($user, $tokenValid));
    }

    private function buatSuperadmin(): User
    {
        return User::factory()->create([
            'email' => 'superadmin.modul4@example.test',
            'password' => self::SANDI_AWAL,
            'role' => User::ROLE_SUPERADMIN,
        ]);
    }
}
