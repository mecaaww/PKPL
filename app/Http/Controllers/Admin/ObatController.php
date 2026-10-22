<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Models\DeskripsiObat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ObatController extends Controller
{
    public function index(Request $request)
    {
        $query = Obat::query();

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('sort')) {
            if ($request->sort === 'price_low') {
                $query->orderBy('harga', 'asc');
            } elseif ($request->sort === 'price_high') {
                $query->orderBy('harga', 'desc');
            } elseif ($request->sort === 'latest') {
                $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->has('diskon') && $request->diskon == '1') {
            $query->where('diskon_persen', '>', 0);
        }

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('low_stok')) {
            $query->where('stok', '<', 5);
        }

        $obats = $query->latest()->paginate(10)->withQueryString();

        return view('admins.produk', compact('obats'));
    }

    public function create()
    {
        return view('admins.create_product');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'          => 'required',
            'harga'         => 'required|integer|min:1|max:9999999',
            'stok'          => 'required|integer|min:0|max:5000',
            'diskon_persen' => 'nullable|integer|min:0|max:100',
            'gambar'        => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'deskripsi'     => 'required|string|min:10|max:1000',
        ], [
            'harga.required'     => 'Harga wajib diisi.',
            'harga.integer'      => 'Harga harus berupa bilangan bulat.',
            'harga.min'          => 'Harga minimal Rp1.',
            'stok.required'      => 'Stok wajib diisi.',
            'stok.min'           => 'Stok minimal 0.',
            'stok.max'           => 'Stok maksimal 5.000.',
            'deskripsi.required' => 'Deskripsi produk wajib diisi.',
            'deskripsi.min'      => 'Deskripsi produk minimal 10 karakter.',
            'gambar.required'    => 'Foto produk wajib diupload.',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->only(['nama', 'kategori', 'harga', 'stok']);
            $data['diskon_persen'] = (int) ($request->diskon_persen ?? 0);

            if ($request->hasFile('gambar')) {
                $data['path_gambar'] = $request->file('gambar')->store('produk', 'public');
            }

            $obat = Obat::create($data);

            // Deskripsi fields dengan label & urutan
            $deskripsiFields = [
                ['label' => 'Product Description', 'nilai' => $request->deskripsi,      'urutan' => 1],
                ['label' => 'Indikasi Umum',       'nilai' => $request->indikasi_umum,  'urutan' => 2],
                ['label' => 'Komposisi',            'nilai' => $request->komposisi,      'urutan' => 3],
                ['label' => 'Dosis',                'nilai' => $request->dosis,          'urutan' => 4],
                ['label' => 'Aturan Pakai',         'nilai' => $request->aturan_pakai,   'urutan' => 5],
                ['label' => 'Kontra Indikasi',      'nilai' => $request->kontra_indikasi,'urutan' => 6],
                ['label' => 'Perhatian',            'nilai' => $request->perhatian,      'urutan' => 7],
                ['label' => 'Efek Samping',         'nilai' => $request->efek_samping,   'urutan' => 8],
                ['label' => 'Kemasan',              'nilai' => $request->kemasan,        'urutan' => 9],
                ['label' => 'Manufaktur',           'nilai' => $request->manufaktur,     'urutan' => 10],
                ['label' => 'No. Registrasi',       'nilai' => $request->no_registrasi,  'urutan' => 11],
            ];

            foreach ($deskripsiFields as $field) {
                if (!empty(trim($field['nilai'] ?? ''))) {
                    DeskripsiObat::create([
                        'obat_id' => $obat->id,
                        'label'   => $field['label'],
                        'nilai'   => trim($field['nilai']),
                        'urutan'  => $field['urutan'],
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('obat.index')->with('success', 'Produk berhasil ditambah');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function edit(Obat $obat)
    {
        return view('admins.edit_product', compact('obat'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama'          => 'required',
            'harga'         => 'required|integer|min:1|max:9999999',
            'stok'          => 'required|integer|min:0|max:5000',
            'diskon_persen' => 'nullable|integer|min:0|max:100',
            'gambar'        => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'deskripsi'     => 'required|string|min:10|max:1000',
        ], [
            'harga.required'     => 'Harga wajib diisi.',
            'harga.min'          => 'Harga minimal Rp1.',
            'stok.required'      => 'Stok wajib diisi.',
            'stok.min'           => 'Stok minimal 0.',
            'stok.max'           => 'Stok maksimal 5.000.',
            'deskripsi.required' => 'Deskripsi produk wajib diisi.',
            'deskripsi.min'      => 'Deskripsi produk minimal 10 karakter.',
        ]);

        DB::beginTransaction();
        try {
            $obat = \App\Models\Obat::findOrFail($id);

            $data = $request->only(['nama', 'kategori', 'harga', 'stok']);
            $data['diskon_persen'] = (int) ($request->diskon_persen ?? 0);

            if ($request->hasFile('gambar')) {
                if ($obat->path_gambar && Storage::exists('public/' . $obat->path_gambar)) {
                    Storage::delete('public/' . $obat->path_gambar);
                }
                $data['path_gambar'] = $request->file('gambar')->store('produk', 'public');
            }

            $obat->update($data);

            // Update/Create semua field deskripsi
            $deskripsiFields = [
                ['label' => 'Product Description', 'nilai' => $request->deskripsi,       'urutan' => 1],
                ['label' => 'Indikasi Umum',       'nilai' => $request->indikasi_umum,   'urutan' => 2],
                ['label' => 'Komposisi',            'nilai' => $request->komposisi,       'urutan' => 3],
                ['label' => 'Dosis',                'nilai' => $request->dosis,           'urutan' => 4],
                ['label' => 'Aturan Pakai',         'nilai' => $request->aturan_pakai,    'urutan' => 5],
                ['label' => 'Kontra Indikasi',      'nilai' => $request->kontra_indikasi, 'urutan' => 6],
                ['label' => 'Perhatian',            'nilai' => $request->perhatian,       'urutan' => 7],
                ['label' => 'Efek Samping',         'nilai' => $request->efek_samping,    'urutan' => 8],
                ['label' => 'Kemasan',              'nilai' => $request->kemasan,         'urutan' => 9],
                ['label' => 'Manufaktur',           'nilai' => $request->manufaktur,      'urutan' => 10],
                ['label' => 'No. Registrasi',       'nilai' => $request->no_registrasi,   'urutan' => 11],
            ];

            foreach ($deskripsiFields as $field) {
                $nilai = trim($field['nilai'] ?? '');
                if (!empty($nilai)) {
                    DeskripsiObat::updateOrCreate(
                        ['obat_id' => $obat->id, 'label' => $field['label']],
                        ['nilai' => $nilai, 'urutan' => $field['urutan']]
                    );
                } else {
                    // Hapus field jika dikosongkan
                    DeskripsiObat::where('obat_id', $obat->id)
                        ->where('label', $field['label'])
                        ->delete();
                }
            }

            DB::commit();
            return redirect()->route('obat.index')->with('success', 'Produk berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updateDiskon(Request $request, $id)
    {
        $request->validate([
            'diskon_persen' => 'required|integer|in:0,5,10,15,20,25,30,40,50',
        ]);

        $obat = \App\Models\Obat::findOrFail($id);
        $obat->update(['diskon_persen' => (int) $request->diskon_persen]);

        $diskon = (int) $obat->diskon_persen;
        if ($diskon === 0) {
            $status = ['label' => 'Tanpa Diskon', 'class' => 'bg-gray-100 text-gray-700 border-gray-300', 'dot' => 'bg-gray-400'];
        } elseif ($diskon <= 10) {
            $status = ['label' => 'Diskon Rendah', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'dot' => 'bg-emerald-500'];
        } elseif ($diskon <= 20) {
            $status = ['label' => 'Diskon Sedang', 'class' => 'bg-amber-50 text-amber-700 border-amber-200', 'dot' => 'bg-amber-500'];
        } else {
            $status = ['label' => 'Diskon Tinggi', 'class' => 'bg-rose-50 text-rose-700 border-rose-200', 'dot' => 'bg-rose-500'];
        }

        return response()->json([
            'success' => true,
            'diskon_persen' => $diskon,
            'status' => $status,
        ]);
    }

    public function destroy(Obat $obat)
    {
        if ($obat->path_gambar) Storage::disk('public')->delete($obat->path_gambar);
        $obat->delete();
        return redirect()->route('obat.index')->with('success', 'Produk berhasil dihapus');
    }
}
