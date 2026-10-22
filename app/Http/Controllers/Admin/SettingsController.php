<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengumumanAdmin;
use App\Models\TargetOmzet;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Tampilkan halaman pengaturan superadmin.
     */
    public function index(): View
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $targetOmzet = TargetOmzet::where('bulan', $currentMonth)
            ->where('tahun', $currentYear)
            ->first();

        $pengumuman = PengumumanAdmin::latest()->first();

        return view('admins.settings.index', compact(
            'targetOmzet',
            'pengumuman',
            'currentMonth',
            'currentYear'
        ));
    }

    /**
     * Perbarui target omzet untuk bulan dan tahun berjalan.
     */
    public function updateTarget(Request $request): RedirectResponse
    {
        $request->validate([
            'target' => [
                'required',
                'integer',
                'min:'.TargetOmzet::MIN_TARGET,
                'max:'.TargetOmzet::MAX_TARGET,
            ],
        ], [
            'target.required' => 'Target omzet wajib diisi.',
            'target.integer' => 'Target omzet harus berupa bilangan bulat.',
            'target.min' => 'Target omzet minimal Rp1.000.000.',
            'target.max' => 'Target omzet maksimal Rp30.000.000.',
        ]);

        $bulan = Carbon::now()->month;
        $tahun = Carbon::now()->year;

        DB::transaction(function () use ($bulan, $tahun, $request) {
            TargetOmzet::updateOrCreate(
                ['bulan' => $bulan, 'tahun' => $tahun],
                ['target' => (int) $request->input('target')]
            );
        });

        return redirect()->route('admin.settings.index')
            ->with('success', 'Target omzet berhasil disimpan.');
    }

    /**
     * Perbarui pengumuman aktif untuk admin.
     */
    public function updateAnnouncement(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), []);

        $validator->after(function ($validator) use ($request) {
            if (! $request->has('konten') || $request->input('konten') === null || $request->input('konten') === '') {
                $validator->errors()->add('konten', 'Pengumuman wajib diisi.');

                return;
            }

            $value = $request->input('konten');

            if (! is_string($value)) {
                $validator->errors()->add('konten', 'Pengumuman harus berupa teks.');

                return;
            }

            if (trim($value) === '') {
                $validator->errors()->add('konten', 'Pengumuman tidak boleh hanya berisi spasi.');

                return;
            }

            if ($value !== strip_tags($value) || preg_match('/<[^>]*>/', $value)) {
                $validator->errors()->add('konten', 'Pengumuman tidak boleh mengandung tag HTML.');

                return;
            }

            $length = mb_strlen(trim($value));

            if ($length < PengumumanAdmin::MIN_LENGTH) {
                $validator->errors()->add('konten', 'Pengumuman minimal 20 karakter.');

                return;
            }

            if ($length > PengumumanAdmin::MAX_LENGTH) {
                $validator->errors()->add('konten', 'Pengumuman maksimal 300 karakter.');

                return;
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $trimmed = trim((string) $request->input('konten'));
        $user = auth()->guard('api')->user();

        DB::transaction(function () use ($trimmed, $user) {
            $existing = PengumumanAdmin::latest()->first();
            if ($existing) {
                $existing->update([
                    'konten' => $trimmed,
                    'user_id' => $user?->id,
                ]);
            } else {
                PengumumanAdmin::create([
                    'konten' => $trimmed,
                    'user_id' => $user?->id,
                ]);
            }
        });

        return redirect()->route('admin.settings.index')
            ->with('success', 'Pengumuman berhasil disimpan.');
    }
}
