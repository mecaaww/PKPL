<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $roleFilter = $request->query('role', 'admin');

        // Superadmin can view admin and pelanggan, never other superadmins
        $allowedRoles = [User::ROLE_ADMIN, User::ROLE_PELANGGAN];
        if (! in_array($roleFilter, $allowedRoles, true)) {
            $roleFilter = 'admin';
        }

        $users = User::where('role', $roleFilter)->latest()->paginate(10);

        return view('admins.users.index', compact('users', 'roleFilter'));
    }

    public function create(Request $request)
    {
        $role = $request->query('role', 'admin');

        return view('admins.users.create', compact('role'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $role = in_array($request->input('role'), [User::ROLE_ADMIN, User::ROLE_PELANGGAN], true)
            ? $request->input('role')
            : User::ROLE_ADMIN;

        User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
        ]);

        $label = $role === User::ROLE_PELANGGAN ? 'pelanggan' : 'admin';

        return redirect()
            ->route('admin.users.index', ['role' => $role])
            ->with('success', "Akun {$label} berhasil dibuat.");
    }

    public function edit(User $user)
    {
        $this->ensureEditable($user);

        return view('admins.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureEditable($user);

        $validated = $request->validate($this->rules($user), $this->messages());
        $data = [
            'username' => $validated['username'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        $label = $user->role === User::ROLE_PELANGGAN ? 'pelanggan' : 'admin';

        return redirect()
            ->route('admin.users.index', ['role' => $user->role])
            ->with('success', "Akun {$label} berhasil diperbarui.");
    }

    public function destroy(User $user)
    {
        $this->ensureEditable($user);

        $role = $user->role;
        $label = $role === User::ROLE_PELANGGAN ? 'pelanggan' : 'admin';

        $user->delete();

        return redirect()
            ->route('admin.users.index', ['role' => $role])
            ->with('success', "Akun {$label} berhasil dihapus.");
    }

    private function rules(?User $user = null): array
    {
        return [
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];
    }

    private function messages(): array
    {
        return [
            'username.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }

    /**
     * Superadmin can only edit admin / pelanggan — never another superadmin.
     */
    private function ensureEditable(User $user): void
    {
        abort_unless(
            in_array($user->role, [User::ROLE_ADMIN, User::ROLE_PELANGGAN], true),
            404
        );
    }
}
