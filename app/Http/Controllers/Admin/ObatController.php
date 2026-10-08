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
            'nama'         => 'required',
            'harga'        => 'required|integer|min:1|max:500000',
            'stok'         => 'required|integer|min:0|max:5000',
            'diskon_persen'=> 'nullable|integer|min:0|max:100',
            'gambar'       => 'image|mimes:jpeg,png,jpg|max:2048',
            'deskripsi'    => 'required|string|min:20|max:1000|regex:/^[^<>]*$/',
        ], [
            'harga.required' => 'Harga wajib diisi.',
            'harga.integer'  => 'Harga harus berupa bilangan bulat.',
            'harga.min'      => 'Harga minimal Rp1.',
            'harga.max'      => 'Harga maksimal Rp500.000.',
            'stok.required'  => 'Stok wajib diisi.',
            'stok.integer'   => 'Stok harus berupa bilangan bulat.',
            'stok.min'       => 'Stok minimal 0.',
            'stok.max'       => 'Stok maksimal 5.000.',
            'deskripsi.required' => 'Deskripsi produk wajib diisi.',
            'deskripsi.string'   => 'Deskripsi produk harus berupa teks.',
            'deskripsi.min'      => 'Deskripsi produk minimal 20 karakter.',
            'deskripsi.max'      => 'Deskripsi produk maksimal 1.000 karakter.',
            'deskripsi.regex'    => 'Deskripsi produk tidak boleh mengandung tag HTML.',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->except(['deskripsi']);
            $data['diskon_persen'] = (int) ($request->diskon_persen ?? 0);

            if ($request->hasFile('gambar')) {
                $data['path_gambar'] = $request->file('gambar')->store('produk', 'public');
            }

            $obat = Obat::create($data);
            $obatId = $obat->id ?: DB::getPdo()->lastInsertId();

            DeskripsiObat::create([
                'obat_id' => $obatId,
                'label' => 'Product Description',
                'nilai' => trim($request->deskripsi),
                'urutan' => 1
            ]);

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
            'nama'         => 'required',
            'harga'        => 'required|integer|min:1|max:500000',
            'stok'         => 'required|integer|min:0|max:5000',
            'diskon_persen'=> 'nullable|integer|min:0|max:100',
            'deskripsi'    => 'required|string|min:20|max:1000|regex:/^[^<>]*$/',
        ], [
            'harga.required' => 'Harga wajib diisi.',
            'harga.integer'  => 'Harga harus berupa bilangan bulat.',
            'harga.min'      => 'Harga minimal Rp1.',
            'harga.max'      => 'Harga maksimal Rp500.000.',
            'stok.required'  => 'Stok wajib diisi.',
            'stok.integer'   => 'Stok harus berupa bilangan bulat.',
            'stok.min'       => 'Stok minimal 0.',
            'stok.max'       => 'Stok maksimal 5.000.',
            'deskripsi.required' => 'Deskripsi produk wajib diisi.',
            'deskripsi.string'   => 'Deskripsi produk harus berupa teks.',
            'deskripsi.min'      => 'Deskripsi produk minimal 20 karakter.',
            'deskripsi.max'      => 'Deskripsi produk maksimal 1.000 karakter.',
            'deskripsi.regex'    => 'Deskripsi produk tidak boleh mengandung tag HTML.',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->except(['_token', '_method', 'deskripsi']);
            $data['diskon_persen'] = (int) ($request->diskon_persen ?? 0);

            $obat = \App\Models\Obat::findOrFail($id);

            if ($request->hasFile('path_gambar')) {
                if ($obat->path_gambar && Storage::exists('public/' . $obat->path_gambar)) {
                    Storage::delete('public/' . $obat->path_gambar);
                }
                $path = $request->file('path_gambar')->store('produk', 'public');
                $data['path_gambar'] = $path;
            }

            $obat->update($data);

            DeskripsiObat::updateOrCreate(
                ['obat_id' => $obat->id, 'label' => 'Product Description'],
                ['nilai' => trim($request->deskripsi), 'urutan' => 1]
            );

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
