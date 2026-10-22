from __future__ import annotations

from copy import deepcopy
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile

from lxml import etree


ROOT = Path(r"C:\Users\Marsya\WebApotek")
W = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
NS = {"w": W}
Q = lambda name: f"{{{W}}}{name}"


def paragraph_text(p):
    return "".join(p.xpath(".//w:t/text()", namespaces=NS))


def set_paragraph_text(p, text):
    ppr = p.find(Q("pPr"))
    rpr = None
    runs = p.findall(Q("r"))
    if runs:
        found = runs[0].find(Q("rPr"))
        if found is not None:
            rpr = deepcopy(found)
    for child in list(p):
        if child is not ppr:
            p.remove(child)
    run = etree.SubElement(p, Q("r"))
    if rpr is not None:
        run.append(rpr)
    t = etree.SubElement(run, Q("t"))
    if text.startswith(" ") or text.endswith(" "):
        t.set("{http://www.w3.org/XML/1998/namespace}space", "preserve")
    t.text = text


def cell_text(tc):
    return "".join(tc.xpath(".//w:t/text()", namespaces=NS))


def set_cell_text(tc, text):
    paragraphs = tc.findall(Q("p"))
    if not paragraphs:
        p = etree.SubElement(tc, Q("p"))
    else:
        p = paragraphs[0]
        for extra in paragraphs[1:]:
            tc.remove(extra)
    set_paragraph_text(p, text)


def append_row(table, template_row):
    cloned = deepcopy(template_row)
    trpr = cloned.find(Q("trPr"))
    if trpr is None:
        trpr = etree.Element(Q("trPr"))
        cloned.insert(0, trpr)
    if trpr.find(Q("cantSplit")) is None:
        etree.SubElement(trpr, Q("cantSplit"))
    table.append(cloned)
    return cloned


def fill_table(table, headers, rows):
    tr_rows = table.findall(Q("tr"))
    if not tr_rows:
        raise ValueError("Expected a table with a header row")
    header = tr_rows[0]
    template = tr_rows[1] if len(tr_rows) > 1 else header
    for row in tr_rows[1:]:
        table.remove(row)
    trpr = header.find(Q("trPr"))
    if trpr is None:
        trpr = etree.Element(Q("trPr"))
        header.insert(0, trpr)
    if trpr.find(Q("tblHeader")) is None:
        etree.SubElement(trpr, Q("tblHeader"))
    for cell, value in zip(header.findall(Q("tc")), headers):
        set_cell_text(cell, value)
    for values in rows:
        row = append_row(table, template)
        for cell, value in zip(row.findall(Q("tc")), values):
            set_cell_text(cell, value)


def find_paragraph(body, exact=None, starts=None):
    for p in body.findall(Q("p")):
        value = paragraph_text(p).strip()
        if exact is not None and value == exact:
            return p
        if starts is not None and value.startswith(starts):
            return p
    raise ValueError(f"Paragraph not found: exact={exact!r}, starts={starts!r}")


def insert_clone_before(anchor, donor, text):
    p = deepcopy(donor)
    set_paragraph_text(p, text)
    anchor.addprevious(p)
    return p


def body_tables(body):
    return body.findall(Q("tbl"))


def patch_package(source, output, edit_document):
    with ZipFile(source, "r") as zin:
        parts = [(item, zin.read(item.filename)) for item in zin.infolist()]
    document_xml = next(data for item, data in parts if item.filename == "word/document.xml")
    root = etree.fromstring(document_xml)
    edit_document(root.find("w:body", namespaces=NS))
    changed = etree.tostring(root, xml_declaration=True, encoding="UTF-8", standalone=True)
    output.parent.mkdir(parents=True, exist_ok=True)
    with ZipFile(output, "w", compression=ZIP_DEFLATED) as zout:
        for item, data in parts:
            zout.writestr(item, changed if item.filename == "word/document.xml" else data)


def patch_module1(body):
    # Keep all existing prose and structure, correcting the selected role and
    # completing the Lab Session requirements against the implemented app.
    for node in body.xpath(".//w:t", namespaces=NS):
        if node.text:
            node.text = node.text.replace("Administrator", "Superadmin").replace("administrator", "superadmin")

    note_replacements = [
        ("Pada Modul 1, arah dan teknik pengujian belum perlu ditentukan", "Teknik awal ditetapkan berdasarkan risiko, variasi input, dan batas yang teridentifikasi. Test condition dan coverage item disusun lebih lanjut pada Modul 2."),
        ("Pertahankan placeholder berikut dan jangan menambahkan nama teknik pada Modul 1.", "Teknik yang direncanakan menjadi dasar penyusunan Test Design Specification pada Modul 2."),
        ("Pada Modul 1, isi hanya jadwal dan luaran yang memang ditetapkan", "Jadwal dan luaran Modul 1 serta Modul 2 dicatat sesuai aktivitas yang telah diselesaikan. Modul 3 sampai Modul 6 dan UAP tetap berupa placeholder sampai dibahas."),
        ("Pada Modul 1, luaran yang perlu diselesaikan adalah dokumen Test Plan", "Modul 1 menghasilkan Test Plan dan entri registrasi proyek. Modul 2 menghasilkan Test Design Specification dan Test Traceability Matrix; luaran modul berikutnya menunggu penetapan."),
    ]
    for p in body.findall(Q("p")):
        current = paragraph_text(p).strip()
        for prefix, replacement in note_replacements:
            if current.startswith(prefix):
                set_paragraph_text(p, replacement)
                break

    tables = body_tables(body)

    def set_row(table_index, row_index, values):
        row = tables[table_index].findall(Q("tr"))[row_index]
        for cell, value in zip(row.findall(Q("tc")), values):
            set_cell_text(cell, value)

    # Document identity and revision history.
    meta = tables[0]
    meta_values = {
        "Identifikasi Test Plan": "TP-WEBAPOTEK-MOD1-202310370311425",
        "Versi": "v1.1",
        "Peran yang Dipilih untuk Diuji": "Superadmin",
        "Nama Praktikan": "Rima Marsya Putri Sabrina",
        "NIM": "202310370311425",
        "Kelas": "Penjaminan Kualitas Perangkat Lunak B",
        "Tanggal Terakhir Diperbarui": "21 Oktober 2026",
        "Status": "Final",
    }
    for row in meta.findall(Q("tr"))[1:]:
        cells = row.findall(Q("tc"))
        if len(cells) >= 2:
            label = cell_text(cells[0]).strip()
            if label in meta_values:
                set_cell_text(cells[1], meta_values[label])

    history = tables[1].findall(Q("tr"))
    if len(history) >= 3:
        set_row(1, 1, ["1.0", "8 Oktober 2026", "", "Penyusunan awal Test Plan WebApotek untuk peran Administrator"])
        set_row(1, 2, ["1.1", "21 Oktober 2026", "Rima Marsya Putri Sabrina", "Penyelarasan Test Plan untuk fokus Superadmin, termasuk strategi, akses, dan fitur khusus Superadmin."])

    # Correct the overview and add the real Superadmin-only feature groups.
    set_row(2, 1, ["Nama Aplikasi", "WebApotek"])
    set_row(2, 6, ["Deskripsi Singkat", "Aplikasi web apotek untuk melihat dan memesan obat, serta mengelola produk, pesanan, akun, target omzet, dan pengumuman. Fokus pengujian pada peran Superadmin."])

    features = [
        ["1", "Login", "Fitur Autentikasi", "Memvalidasi email dan kata sandi, lalu mengarahkan Superadmin ke dashboard."] ,
        ["2", "Register", "Fitur Autentikasi", "Mendaftarkan akun pelanggan dengan nama, email unik, dan kata sandi minimal delapan karakter; pendaftaran publik tidak membuat akun admin atau Superadmin."],
        ["3", "Forgot Password", "Fitur Autentikasi", "Mengirim tautan reset dan memperbarui kata sandi melalui token yang valid."],
        ["4", "Validasi Harga dan Stok", "Fitur Tambahan", "Membatasi harga Rp1-Rp500.000 dan stok 0-5.000 menggunakan bilangan bulat."],
        ["5", "Validasi Nama dan Deskripsi", "Fitur Tambahan", "Memastikan data teks wajib diisi; deskripsi terdiri dari 20-1.000 karakter dan tidak memuat tag HTML."],
        ["6", "CRUD Produk", "Fitur Tambahan", "Superadmin dapat menambah, melihat, mengubah, dan menghapus data produk obat."],
        ["7", "Manajemen Diskon", "Fitur Tambahan", "Mengubah diskon melalui pilihan yang tersedia serta menampilkan status dan warna yang sesuai."],
        ["8", "Pencarian dan Filter Produk", "Fitur Tambahan", "Menggabungkan pencarian nama, kategori, urutan harga, diskon, dan filter stok rendah."],
        ["9", "Manajemen Akun Admin dan Pelanggan", "Fitur Khusus Superadmin", "Membuat, melihat, mengubah, dan menghapus akun admin atau pelanggan; akun Superadmin lain tidak dapat dikelola melalui menu ini."],
        ["10", "Pengaturan Target Omzet", "Fitur Khusus Superadmin", "Menyimpan target omzet bulan berjalan dalam bilangan bulat Rp1.000.000-Rp30.000.000."],
        ["11", "Manajemen Pengumuman", "Fitur Khusus Superadmin", "Menyimpan pengumuman teks 20-300 karakter tanpa tag HTML untuk ditampilkan pada dashboard admin."],
        ["12", "Pemantauan Pesanan", "Fitur Tambahan", "Melihat daftar dan rincian pesanan, memfilter status, serta memperbarui status pesanan pelanggan."],
    ]
    feature_table = tables[3]
    template = feature_table.findall(Q("tr"))[1]
    current = feature_table.findall(Q("tr"))
    for row in current[1:]:
        feature_table.remove(row)
    for record in features:
        row = append_row(feature_table, template)
        for cell, value in zip(row.findall(Q("tc")), record):
            set_cell_text(cell, value)

    # Scope is centered on Superadmin access and operations; customer shopping
    # and infrastructure remain outside the selected role's test scope.
    set_row(4, 1, ["1", "Keranjang, checkout, dan pembayaran pelanggan", "Alur transaksi pelanggan tidak menjadi fokus peran Superadmin pada Test Plan ini."])
    set_row(4, 2, ["2", "Pembayaran otomatis dan infrastruktur produksi", "WebApotek hanya menampilkan instruksi transfer; pengujian dibatasi pada perilaku aplikasi di lingkungan lokal."])

    set_row(5, 1, ["Nama Peran", "Superadmin"])
    set_row(5, 2, ["Deskripsi Peran", "Mengelola produk, pesanan, akun admin dan pelanggan, serta pengaturan operasional. Superadmin juga dapat menjalankan fungsi admin yang diizinkan."])
    set_row(5, 3, ["Fitur yang Dapat Diakses", "Login dan reset kata sandi; dashboard; produk dan diskon; daftar serta pembaruan status pesanan; manajemen akun admin/pelanggan; pengaturan target omzet dan pengumuman."])
    set_row(5, 4, ["Alasan Pemilihan", "Superadmin dipilih karena dapat menguji alur admin sekaligus fitur khusus pengelolaan akun dan pengaturan operasional dengan satu role yang konsisten."])

    set_row(6, 1, ["Asumsi", "Aplikasi dapat dijalankan secara lokal dan basis data telah berisi data produk."])
    set_row(6, 2, ["Asumsi", "Akun Superadmin tersedia dan dapat digunakan selama pengujian; akun awal dapat disiapkan melalui perintah aplikasi."])
    set_row(6, 3, ["Batasan", "Pengujian utama dibatasi pada role Superadmin dan fitur yang dinyatakan dalam cakupan."])
    set_row(6, 4, ["Batasan", "Pengujian dilakukan pada lingkungan lokal dan tidak mencakup pembayaran otomatis atau infrastruktur server produksi."])

    risk_rows = [
        ["R-01", "Admin biasa atau pelanggan dapat membuka halaman dan endpoint khusus Superadmin melalui URL langsung.", "Produk", "5", "3", "15", "Periksa middleware role pada route dan uji akses langsung dengan akun pelanggan, admin, dan Superadmin."],
        ["R-02", "Harga atau stok di luar batas tersimpan karena validasi tambah dan ubah produk tidak konsisten.", "Produk", "4", "2", "8", "Uji validasi server yang sama pada proses tambah dan ubah, termasuk nilai batas dan di luar batas."],
        ["R-03", "Deskripsi produk kosong, di luar panjang yang diizinkan, atau mengandung tag HTML tersimpan.", "Produk", "3", "2", "6", "Uji deskripsi 20-1.000 karakter dan input yang mengandung markup pada create dan update."],
        ["R-04", "Target omzet atau pengumuman Superadmin menerima nilai yang tidak valid atau tidak tampil pada dashboard admin.", "Produk", "4", "3", "12", "Uji batas target Rp1.000.000-Rp30.000.000 dan pengumuman 20-300 karakter; pastikan data tersimpan dan tampil."],
        ["R-05", "Superadmin dapat mengubah akun Superadmin lain atau akun yang dibuat menerima role yang tidak dimaksudkan.", "Produk", "5", "2", "10", "Uji pembatasan daftar dan edit akun Superadmin, role hasil registrasi publik, serta nilai role saat pembuatan akun."],
        ["R-06", "Waktu praktikum tidak cukup untuk menyiapkan akun, data, dan bukti pengujian.", "Proyek", "3", "3", "9", "Siapkan akun lintas role, data produk/pesanan, nilai batas, dan daftar bukti sebelum sesi praktikum."],
    ]
    fill_table(tables[7], ["ID Risiko", "Deskripsi Risiko", "Jenis", "Dampak", "Kemungkinan", "Prioritas", "Strategi Mitigasi"], risk_rows)

    strategy_rows = [
        ["Pendekatan Pengujian", "Pengujian black-box fungsional pada alur Superadmin, mencakup autentikasi, otorisasi role, produk, akun, pengaturan, dashboard, dan pesanan."],
        ["Tingkat Pengujian", "Pengujian sistem melalui browser pada aplikasi lokal; interaksi route dan penyimpanan data diperiksa bersama hasil yang terlihat."],
        ["Jenis Pengujian Utama", "Fungsional, validasi input, kontrol akses, pengujian ulang, dan regresi pada fitur yang terdampak perubahan."],
        ["Fitur Berprioritas Tinggi", "Pembatasan akses fitur Superadmin, manajemen akun, validasi target omzet, dan penyimpanan pengumuman."],
    ]
    fill_table(tables[8], ["Bidang", "Deskripsi"], strategy_rows)

    set_row(9, 1, ["Arah dan Teknik Pengujian", "Ditetapkan", "Equivalence Partitioning untuk kelas input dan role; Boundary Value Analysis untuk harga, stok, target omzet, serta panjang pengumuman; pemeriksaan otorisasi berdasarkan kombinasi role dan route."])

    set_row(10, 1, ["Kriteria Masuk", "Aplikasi dapat dijalankan, basis data tersedia, akun Superadmin siap, dan fitur dalam cakupan dapat diakses."])
    set_row(10, 3, ["Kriteria Lolos", "Hasil aktual sesuai dengan hasil yang diharapkan dan role yang tidak berwenang tidak dapat membuka atau mengubah fitur Superadmin."])
    set_row(10, 4, ["Kriteria Gagal", "Hasil aktual berbeda, validasi tidak bekerja, kontrol akses dapat dilewati, atau aplikasi mengalami error."])
    set_row(10, 5, ["Pengujian Ulang", "Kasus yang gagal dijalankan kembali setelah perbaikan diterapkan."])
    set_row(10, 6, ["Pengujian Regresi", "Fitur utama produk, akun, pengaturan, autentikasi, dan otorisasi diperiksa kembali setelah perubahan."])

    set_row(14, 1, ["Akun Uji Valid", "Akun Superadmin, admin biasa, dan pelanggan dengan kredensial valid untuk memeriksa hak akses masing-masing."])
    set_row(14, 2, ["Akun Uji Tidak Valid", "Kredensial salah, email tidak terdaftar, role yang mencoba akses tanpa izin, dan percobaan mengelola akun Superadmin lain."])
    set_row(14, 3, ["Contoh Data Input", "Data produk valid dan invalid; harga Rp1-Rp500.000; stok 0-5.000; diskon yang diizinkan; akun admin/pelanggan; target omzet Rp1.000.000-Rp30.000.000; pengumuman 20-300 karakter."])
    set_row(14, 4, ["Data Uji Tambahan", "Data pesanan dengan beberapa status, serta data dashboard yang memiliki target omzet dan pengumuman aktif."])

    set_row(15, 1, ["Modul 1", "Penyelesaian Test Plan, finalisasi risk register dan strategi, serta registrasi proyek", "9 Oktober 2026", "Dokumen Test Plan dan entri registrasi proyek"])
    set_row(15, 2, ["Modul 2", "Analisis kebutuhan, penyusunan Test Design Specification, dan pemetaan traceability", "Sesi Lab Modul 2", "Test Design Specification dan Test Traceability Matrix"])
    set_row(16, 1, ["Modul 1", "Dokumen Test Plan", "DOCX", "Final - siap didemonstrasikan"])
    set_row(16, 2, ["Modul 1", "Entri Lembar Registrasi Proyek", "Google Sheets", "Sedang Berjalan"])
    set_row(16, 3, ["Modul 2", "Test Design Specification dan Test Traceability Matrix", "DOCX", "Final - siap didemonstrasikan"])


def patch_module2(body):
    title = find_paragraph(body, exact="TEST DESIGN SPECIFICATION (TDS)")
    req_heading = find_paragraph(body, exact="1. Requirements")
    feature_heading = find_paragraph(body, exact="2. Feature Sets")
    conditions_heading = find_paragraph(body, exact="3. Test Conditions")
    coverage_heading = find_paragraph(body, exact="4. Coverage Items")
    ttm_heading = find_paragraph(body, exact="5. Test Traceability Matrix (TTM)")
    intro = find_paragraph(body, starts="The following example demonstrates")

    # Add student identity in the same compact, plain style used in the existing
    # completed pre-lab answer. The title and section hierarchy remain intact.
    req_donor = next(p for p in body.findall(Q("p")) if paragraph_text(p).startswith("RQ-001:"))
    for text in [
        "Nama: Rima Marsya Putri Sabrina",
        "NIM: 202310370311425",
        "Kelas: Penjaminan Kualitas Perangkat Lunak B",
    ]:
        title.addprevious(deepcopy(req_donor))
        set_paragraph_text(title.getprevious(), text)

    requirements = [
        "RQ-001: Sistem harus mengizinkan Superadmin masuk menggunakan kredensial valid dan membuka dashboard admin.",
        "RQ-002: Pendaftaran publik harus membuat akun pelanggan dan tidak boleh memberikan role admin atau Superadmin.",
        "RQ-003: Pengguna dengan email terdaftar harus dapat mengatur ulang kata sandi melalui token yang valid.",
        "RQ-004: Akses dashboard hanya diberikan kepada admin dan Superadmin, sedangkan menu akun dan pengaturan hanya dapat digunakan Superadmin.",
        "RQ-005: Superadmin harus dapat mengelola produk dengan harga Rp1-Rp500.000, stok 0-5.000, diskon 0-100, dan deskripsi teks 20-1.000 karakter tanpa tag HTML.",
        "RQ-006: Superadmin harus dapat mengubah diskon produk menggunakan pilihan 0, 5, 10, 15, 20, 25, 30, 40, atau 50 persen dengan status tampilan yang sesuai.",
        "RQ-007: Superadmin harus dapat mencari dan memfilter produk menurut nama, kategori, urutan harga, diskon, dan stok rendah.",
        "RQ-008: Superadmin harus dapat membuat, melihat, mengubah, dan menghapus akun admin serta pelanggan dengan email unik dan aturan kata sandi yang berlaku.",
        "RQ-009: Superadmin tidak boleh mengubah atau menghapus akun Superadmin lain melalui manajemen akun.",
        "RQ-010: Superadmin harus dapat menyimpan target omzet bulan berjalan sebagai bilangan bulat antara Rp1.000.000 dan Rp30.000.000.",
        "RQ-011: Superadmin harus dapat menyimpan pengumuman teks sepanjang 20-300 karakter tanpa tag HTML.",
        "RQ-012: Dashboard admin harus menampilkan target omzet bulan berjalan dan pengumuman terbaru jika tersedia.",
        "RQ-013: Superadmin harus dapat melihat dan memfilter pesanan pelanggan, membuka rincian pesanan, serta memperbarui statusnya.",
    ]
    req_ps = [p for p in body.findall(Q("p")) if paragraph_text(p).startswith("RQ-")]
    for p, text in zip(req_ps, requirements):
        set_paragraph_text(p, text)
    for text in requirements[len(req_ps):]:
        insert_clone_before(feature_heading, req_donor, text)

    feature_sets = [
        ("FS-001 - Autentikasi dan Otorisasi", "Objective: Menguji login Superadmin, pendaftaran publik, reset kata sandi, dan pembatasan akses berdasarkan role.", "Priority: Tinggi", "Traceability: RQ-001, RQ-002, RQ-003, RQ-004"),
        ("FS-002 - Pengelolaan Produk", "Objective: Mengelola produk, aturan input, diskon, pencarian, serta filter produk.", "Priority: Tinggi", "Traceability: RQ-005, RQ-006, RQ-007"),
        ("FS-003 - Manajemen Akun", "Objective: Mengelola akun admin dan pelanggan sambil melindungi akun Superadmin lain.", "Priority: Tinggi", "Traceability: RQ-008, RQ-009"),
        ("FS-004 - Dashboard dan Pengaturan Operasional", "Objective: Mengelola target omzet dan pengumuman serta memeriksa informasi yang ditampilkan pada dashboard.", "Priority: Tinggi", "Traceability: RQ-010, RQ-011, RQ-012"),
        ("FS-005 - Pemantauan Pesanan", "Objective: Meninjau daftar dan rincian pesanan serta memperbarui status penanganannya.", "Priority: Sedang", "Traceability: RQ-013"),
    ]
    feature_ps = []
    collecting = False
    for p in body.findall(Q("p")):
        text = paragraph_text(p).strip()
        if p is feature_heading:
            collecting = True
            continue
        if p is conditions_heading:
            break
        if collecting and text:
            feature_ps.append(p)
    donors = feature_ps[:4]
    need = len(feature_sets) * 4
    while len(feature_ps) < need:
        donor = donors[len(feature_ps) % 4]
        p = deepcopy(donor)
        conditions_heading.addprevious(p)
        feature_ps.append(p)
    for p, text in zip(feature_ps, [line for fs in feature_sets for line in fs]):
        set_paragraph_text(p, text)

    test_conditions = [
        ["TCond-001", "FS-001", "Superadmin masuk dengan email dan kata sandi valid, lalu diarahkan ke dashboard admin.", "Tinggi", "RQ-001"],
        ["TCond-002", "FS-001", "Login dengan email atau kata sandi salah ditolak dan tidak memberikan akses ke dashboard.", "Tinggi", "RQ-001"],
        ["TCond-003", "FS-001", "Pendaftaran publik selalu menghasilkan role pelanggan, termasuk saat permintaan mencoba mengirim role admin atau Superadmin.", "Tinggi", "RQ-002"],
        ["TCond-004", "FS-001", "Reset kata sandi berhasil dengan email terdaftar, token valid, dan konfirmasi kata sandi yang cocok.", "Tinggi", "RQ-003"],
        ["TCond-005", "FS-001", "Reset kata sandi ditolak untuk token tidak valid/kedaluwarsa atau konfirmasi kata sandi yang tidak cocok.", "Tinggi", "RQ-003"],
        ["TCond-006", "FS-001", "Pelanggan tidak dapat mengakses panel admin; admin biasa tidak dapat membuka akun atau pengaturan; Superadmin dapat mengakses menu sesuai role.", "Tinggi", "RQ-004"],
        ["TCond-007", "FS-002", "Create dan update produk menerima nilai batas yang valid serta menolak harga/stok di luar rentang dan deskripsi yang tidak sesuai aturan.", "Tinggi", "RQ-005"],
        ["TCond-008", "FS-002", "Superadmin menambah, melihat, mengubah, dan menghapus produk; perubahan tersimpan pada daftar produk.", "Tinggi", "RQ-005"],
        ["TCond-009", "FS-002", "Diskon pada nilai yang diizinkan tersimpan dengan label status yang tepat; nilai selain daftar ditolak.", "Tinggi", "RQ-006"],
        ["TCond-010", "FS-002", "Kombinasi pencarian nama, kategori, urutan harga, diskon, dan stok rendah menghasilkan daftar sesuai semua filter.", "Sedang", "RQ-007"],
        ["TCond-011", "FS-003", "Superadmin membuat, membuka, memperbarui, dan menghapus akun admin atau pelanggan dengan data valid.", "Tinggi", "RQ-008"],
        ["TCond-012", "FS-003", "Manajemen akun menolak email duplikat, format email salah, kata sandi pendek, atau konfirmasi yang berbeda.", "Tinggi", "RQ-008"],
        ["TCond-013", "FS-003", "Akun Superadmin lain tidak muncul sebagai akun yang dapat dikelola dan akses langsung untuk edit/hapus ditolak.", "Tinggi", "RQ-009"],
        ["TCond-014", "FS-004", "Target omzet berupa bilangan bulat pada batas Rp1.000.000 atau Rp30.000.000 tersimpan untuk bulan berjalan.", "Tinggi", "RQ-010"],
        ["TCond-015", "FS-004", "Target omzet kosong, bukan bilangan bulat, di bawah Rp1.000.000, atau di atas Rp30.000.000 ditolak.", "Tinggi", "RQ-010"],
        ["TCond-016", "FS-004", "Pengumuman teks valid pada batas 20 dan 300 karakter tersimpan; teks kosong, hanya spasi, di luar batas, atau bertag HTML ditolak.", "Tinggi", "RQ-011"],
        ["TCond-017", "FS-004", "Dashboard admin menampilkan target omzet bulan berjalan dan pengumuman terbaru yang sudah disimpan.", "Sedang", "RQ-012"],
        ["TCond-018", "FS-005", "Superadmin memfilter pesanan, membuka rincian pesanan, dan menyimpan status baru yang tampil pada data pesanan.", "Tinggi", "RQ-013"],
    ]

    coverage_items = [
        ["CI-001", "Penerimaan kredensial valid dan pengalihan ke dashboard Superadmin", "TCond-001", "Tinggi", "RQ-001"],
        ["CI-002", "Penolakan kredensial tidak valid dan tidak adanya akses dashboard", "TCond-002", "Tinggi", "RQ-001"],
        ["CI-003", "Role pelanggan hasil pendaftaran publik dan penolakan eskalasi role", "TCond-003", "Tinggi", "RQ-002"],
        ["CI-004", "Validitas token, kecocokan konfirmasi, dan penyimpanan kata sandi baru", "TCond-004", "Tinggi", "RQ-003"],
        ["CI-005", "Penolakan token reset tidak valid atau konfirmasi berbeda", "TCond-005", "Tinggi", "RQ-003"],
        ["CI-006", "Matriks akses pelanggan, admin, dan Superadmin terhadap route dan menu terbatas", "TCond-006", "Tinggi", "RQ-004"],
        ["CI-007", "Batas harga, stok, diskon umum, dan panjang/format deskripsi produk", "TCond-007", "Tinggi", "RQ-005"],
        ["CI-008", "Persistensi setiap operasi CRUD produk", "TCond-008", "Tinggi", "RQ-005"],
        ["CI-009", "Nilai diskon yang diizinkan dan pemetaan label status", "TCond-009", "Tinggi", "RQ-006"],
        ["CI-010", "Kebenaran hasil gabungan filter produk", "TCond-010", "Sedang", "RQ-007"],
        ["CI-011", "Operasi akun admin/pelanggan dan role yang diberikan", "TCond-011", "Tinggi", "RQ-008"],
        ["CI-012", "Email unik serta aturan panjang dan konfirmasi kata sandi", "TCond-012", "Tinggi", "RQ-008"],
        ["CI-013", "Perlindungan akun Superadmin dari edit/hapus oleh fitur manajemen akun", "TCond-013", "Tinggi", "RQ-009"],
        ["CI-014", "Batas target omzet dan periode bulan/tahun berjalan", "TCond-014", "Tinggi", "RQ-010"],
        ["CI-015", "Penolakan target kosong, pecahan, dan di luar rentang", "TCond-015", "Tinggi", "RQ-010"],
        ["CI-016", "Panjang, whitespace, dan larangan tag HTML pada pengumuman", "TCond-016", "Tinggi", "RQ-011"],
        ["CI-017", "Data target dan pengumuman yang tampil di dashboard admin", "TCond-017", "Sedang", "RQ-012"],
        ["CI-018", "Filter daftar, detail pesanan, dan persistensi status", "TCond-018", "Tinggi", "RQ-013"],
    ]

    ttm = []
    relation_rows = [
        ("RQ-001", "FS-001", "TCond-001", "CI-001"), ("RQ-001", "FS-001", "TCond-002", "CI-002"),
        ("RQ-002", "FS-001", "TCond-003", "CI-003"), ("RQ-003", "FS-001", "TCond-004", "CI-004"),
        ("RQ-003", "FS-001", "TCond-005", "CI-005"), ("RQ-004", "FS-001", "TCond-006", "CI-006"),
        ("RQ-005", "FS-002", "TCond-007", "CI-007"), ("RQ-005", "FS-002", "TCond-008", "CI-008"),
        ("RQ-006", "FS-002", "TCond-009", "CI-009"), ("RQ-007", "FS-002", "TCond-010", "CI-010"),
        ("RQ-008", "FS-003", "TCond-011", "CI-011"), ("RQ-008", "FS-003", "TCond-012", "CI-012"),
        ("RQ-009", "FS-003", "TCond-013", "CI-013"), ("RQ-010", "FS-004", "TCond-014", "CI-014"),
        ("RQ-010", "FS-004", "TCond-015", "CI-015"), ("RQ-011", "FS-004", "TCond-016", "CI-016"),
        ("RQ-012", "FS-004", "TCond-017", "CI-017"), ("RQ-013", "FS-005", "TCond-018", "CI-018"),
    ]
    for requirement, feature, condition, item in relation_rows:
        ttm.append([requirement, feature, condition, item])

    set_paragraph_text(intro, "Matriks berikut menelusuri setiap kebutuhan WebApotek ke feature set, test condition, dan coverage item yang memeriksanya.")
    tables = body_tables(body)
    fill_table(tables[0], ["ID", "Feature Set", "Description", "Priority", "Traceability"], test_conditions)
    fill_table(tables[1], ["ID", "Description", "Related Test Condition", "Priority", "Traceability"], coverage_items)
    fill_table(tables[2], ["Requirement", "Feature Set", "Test Condition", "Coverage Item"], ttm)


module1_source = ROOT / "output" / "documents" / "Test Plan WebApotek Admin.docx"
module1_output = ROOT / "output" / "documents" / "Test Plan WebApotek Superadmin Modul 1.docx"
module2_source = Path(r"D:\KULIAH SEMESTER 7\PENJAMINAN KUALITAS PERANGKAT LUNAK\MODUL 2\PRELAB\Test Design Specification & TTM Template.docx")
module2_output = ROOT / "output" / "documents" / "TDS TTM WebApotek Superadmin Modul 2.docx"

patch_package(module1_source, module1_output, patch_module1)
patch_package(module2_source, module2_output, patch_module2)
print(module1_output)
print(module2_output)
