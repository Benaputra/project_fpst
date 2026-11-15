# System Design & Architecture Guide — Tema Sage Botanical (FPST UPB)

Dokumen ini merupakan panduan **Design System** (format pewarnaan, tipografi, komponen UI, dokumen cetak/PDF) dan **Arsitektur Perangkat Lunak** yang diekstrak dari proyek `project_fpst` (Fakultas Pertanian, Sains dan Teknologi — Universitas Panca Bhakti). Dokumen ini dirancang sebagai referensi baku (*reusable blueprint*) bagi pengembangan program-program lain yang bertemakan pertanian, agribisnis, agroteknologi, maupun layanan akademik/instansi terkait.

---

## 1. Filosofi Desain

Tema visual menggunakan pendekatan **"Sage Botanical & Clean Academic"** — memadukan warna hijau sage alami yang tenang, permukaan kanvas terang yang bersih (*unboxed & clean*), kontras teks gelap bernuansa hutan (*deep forest*), serta aksen emas panen (*harvest gold*). Pendekatan ini menjaga keterbacaan tinggi pada tabel data, formulir panjang, dan alur administrasi tanpa membuat mata cepat lelah.

---

## 2. Sistem Pewarnaan (Color System)

### 2.1 Core Brand & Surface Tokens (`:root` CSS Variables)

Token utama didefinisikan pada [layouts/app.blade.php](file:///c:/Coding/laravel/project_fpst/resources/views/layouts/app.blade.php#L13-L27) dan [landing.blade.php](file:///c:/Coding/laravel/project_fpst/resources/views/landing.blade.php#L16-L29):

| Token / Variabel CSS | Nilai Hex | Peran & Penggunaan |
|---|---|---|
| `--primary` | `#446850` | Warna brand utama (Sage Green): tombol utama, tab aktif, nav aktif, link hover, ikon/aksen utama |
| `--primary-hover` | `#35533f` | Kondisi `:hover` pada tombol/interaktif primer, teks pill hero |
| `--primary-light` | `#eaf0eb` | Latar lembut hover pagination, pill badge, highlight fokus (`#eaf1eb` pada form ring) |
| `--primary-border` | `#b8ccbe` | Border lembut untuk pill, outline button, pembatas garis putus-putus alur |
| `--forest-dark` / `--sidebar-bg` | `#18241c` | Latar sidebar aplikasi, teks metrik gelap, kontras elemen gelap |
| `--forest-mid` / `--sidebar-border` | `#223328` | Garis pemisah internal sidebar |
| `--sidebar-hover` | `#24352a` | Latar item navigasi sidebar saat hover |
| `--canvas` | `#f3f6f3` | Latar belakang utama halaman (*warm sage-tinted off-white*) |
| `--card-bg` | `#ffffff` | Permukaan kartu (*card*), topbar, input form, modal dialog |
| `--text-main` | `#142017` | Warna teks utama (judul, isi konten, heading) |
| `--text-muted` | `#576d5c` | Teks sekunder, label deskripsi, subtitle, placeholder kontekstual |
| `--border` | `#dbe4dc` | Garis pemisah tabel, card header, navbar, dan topbar |
| `--accent-gold` | `#c79a3b` | Aksen pendukung bertema panen/prestasi (landing page / highlight non-primer) |

### 2.2 Warna Turunan Teks, Kontrol & Sidebar

| Elemen | Nilai Hex | Keterangan |
|---|---|---|
| Teks Heading Gelap / Stat Value | `#1c2b20` | Judul modal, angka statistik besar, penekanan data tabel |
| Teks Label Form | `#2b3d30` | Label input (`0.85rem`, weight `600`) |
| Teks Sekunder / Header Tabel | `#425848` | Header `<th>` tabel |
| Latar Header Tabel | `#f4f8f4` | Latar `<th>` agar terpisah halus dari baris data |
| Hover Baris Tabel | `#f7faf7` | Latar `<tr>:hover td` |
| Latar Tombol Sekunder | `#eef3ee` (hover `#e1ebe2`) | Teks `#2c3f31`, border `#cbd8ce` |
| Teks Navigasi Sidebar | `#ccd8ce` (submenu label `#748c79`, subtitle `#9cb1a0`) | Teks terang di atas latar `#18241c` |
| Border Tombol Logout Sidebar | `#334a3b` | Teks `#d1ded4`, hover berubah merah bahaya `#dc2626` |
| Backdrop Blur Modal | `rgba(15, 23, 42, 0.65)` | Dipadukan dengan `backdrop-filter: blur(10px)` |
| Backdrop Page Loader | `rgba(243, 246, 243, 0.75)` | Dipadukan dengan `backdrop-filter: blur(4px)` |
| Gradient Overlay Foto Lapangan/Kampus | `linear-gradient(to top, rgba(24, 36, 28, 0.88) 0%, rgba(24, 36, 28, 0.45) 60%, transparent 100%)` | Menjamin teks putih tetap kontras di atas foto pertanian/kampus |

### 2.3 Warna Semantik (Alert, Status & Badge)

#### A. Alert / Notifikasi Banner
| Jenis Alert | Background | Border | Teks |
|---|---|---|---|
| **Success** (`.alert-success`) | `#f0fdf4` | `#bbf7d0` | `#166534` |
| **Error / Danger** (`.alert-error`) | `#fef2f2` | `#fecaca` | `#991b1b` |
| **Info** (`.alert-info`) | `#f1f7f2` | `#c0d8c4` | `#2c4a35` |
| **Warning** (`.alert-warning`) | `#fffbeb` | `#fde68a` | `#92400e` |

#### B. Badge Status Alur Kerja
| Status | CSS Custom Class | Tailwind Equivalent (Enum) | Visual |
|---|---|---|---|
| **Diajukan / Menunggu** | `bg: #fef3c7`, `color: #92400e`, `border: #fde68a` | `bg-amber-50 text-amber-700 border-amber-200` | Kuning amber hangat |
| **Diproses / Berjalan** | `bg: #eaf1eb`, `color: #35533f`, `border: #bfd3c3` | `bg-blue-50 text-blue-700 border-blue-200` | Sage lembut / biru proses |
| **Selesai / Disetujui** | `bg: #dcfce7`, `color: #166534`, `border: #bbf7d0` | `bg-emerald-50 text-emerald-700 border-emerald-200` | Hijau zamrud sukses |
| **Ditolak / Bahaya** | `bg: #fee2e2`, `color: #991b1b`, `border: #fecaca` | `bg-rose-50 text-rose-700 border-rose-200` | Merah lembut peringatan |
| **Badge Counter Notifikasi** | `bg: #e11d48`, `color: #ffffff` | `bg-rose-600 text-white` | Lingkaran merah kontras |

### 2.4 Snippet CSS Tokens Siap Pakai (Copy-Paste untuk Proyek Baru)

```css
:root {
    /* Primary Botanical Sage */
    --primary: #446850;
    --primary-hover: #35533f;
    --primary-light: #eaf0eb;
    --primary-border: #b8ccbe;

    /* Dark Forest & Sidebar */
    --forest-dark: #18241c;
    --forest-mid: #223328;
    --sidebar-bg: #18241c;
    --sidebar-hover: #24352a;
    --sidebar-border: #223328;

    /* Neutral Surfaces & Text */
    --canvas: #f3f6f3;
    --card-bg: #ffffff;
    --text-main: #142017;
    --text-muted: #576d5c;
    --border: #dbe4dc;

    /* Agricultural Accent */
    --accent-gold: #c79a3b;
}
```

---

## 3. Sistem Tipografi (Font & Skala Ukuran)

### 3.1 Font Family
1. **Antarmuka Web (UI & Landing Page)**:
   - **Utama**: `'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif`
   - **Bobot yang dimuat** (Google Fonts): `400` (Regular), `500` (Medium), `600` (SemiBold), `700` (Bold), `800` (ExtraBold).
   - **Catatan Konfigurasi Tailwind CSS v4** ([resources/css/app.css](file:///c:/Coding/laravel/project_fpst/resources/css/app.css#L9-L12)): bawaan template masih mendefinisikan `--font-sans: 'Instrument Sans'`, sementara layout aktif memuat **Plus Jakarta Sans** langsung via `<link>` di `<head>`. Untuk proyek baru berbasis Vite + Tailwind v4, samakan `--font-sans` ke `'Plus Jakarta Sans'`.
2. **Dokumen Resmi & Cetak PDF (Dompdf)** ([surat-undangan-seminar.blade.php](file:///c:/Coding/laravel/project_fpst/resources/views/pdf/surat-undangan-seminar.blade.php#L6-L66)):
   - **Font**: `"Times New Roman", Times, serif` (kompatibel penuh dengan `Times-Roman` pada Dompdf).
   - **Warna Teks & Garis**: `#000000` murni untuk standar surat resmi/SK.
   - **Setup Kertas**: `@page { margin: 16mm 20mm 16mm 20mm; size: a4 portrait; }`.

### 3.2 Skala Tipografi UI

| Elemen UI | Ukuran (`font-size`) | Bobot (`font-weight`) | Karakteristik Tambahan |
|---|---|---|---|
| **Hero Title (Landing)** | `clamp(2rem, 3.6vw, 3rem)` | `800` | `line-height: 1.15`, `letter-spacing: -0.025em` |
| **Section Title (Landing)** | `clamp(1.55rem, 2.6vw, 2.15rem)` | `800` | `line-height: 1.25`, `letter-spacing: -0.02em` |
| **Stat Box Value** | `1.6rem` (mobile `1.35rem`) | `800` | Warna `#1c2b20` |
| **Topbar & Card Title** | `1.05rem` | `700` | Ringkas, satu baris di topbar |
| **Body / Tabel / Tombol** | `0.88rem` – `0.92rem` | `400` – `600` | Input form `0.92rem` agar nyaman dibaca di mobile |
| **Label Form & Tab** | `0.85rem` | `600` | Warna `#2b3d30` / `--text-muted` |
| **Navigasi Sidebar** | `0.88rem` | `550` (active `600`) | Gap ikon-teks `0.75rem` |
| **Badge / Helper / Eyebrow** | `0.72rem` – `0.78rem` | `600` – `800` | Header tabel & menu label: `uppercase`, `letter-spacing: 0.05em–0.08em` |

### 3.3 Skala Tipografi Dokumen PDF Resmi (KOP & Isi Surat)

| Elemen Dokumen | Ukuran | Bobot & Jarak |
|---|---|---|
| **Isi Surat Standar** | `11pt` | `line-height: 1.35` |
| **KOP Nama Institusi / Universitas** | `15.5pt` | `bold`, `letter-spacing: 0.5px`, `line-height: 1.15` |
| **KOP Nama Fakultas / Unit** | `14pt` | `bold`, `letter-spacing: 0.3px`, `line-height: 1.15` |
| **KOP Program Studi / Sub-unit** | `9pt` | `bold` |
| **KOP Alamat & Kontak** | `8.5pt` | Regular |
| **Garis Pembatas KOP** | `border-top: 3px solid #000000; border-bottom: 1px solid #000000; height: 2px;` | Garis ganda klasik tata naskah dinas |

---

## 4. Sistem Komponen, Layout & Motion

### 4.1 Struktur Layout & Responsivitas
- **Desktop (`>= 769px`)**: `flex` shell dengan **Sidebar kiri sticky** (`width: 260px`, `height: 100vh`) + area konten fleksibel (`min-width: 0`) dan **Topbar sticky** (`padding: 0.75rem 2rem`).
- **Mobile (`<= 768px`)**: Sidebar berubah menjadi *slide-in drawer* dengan backdrop blur; muncul tombol hamburger di topbar, padding konten menyesuaikan (`padding: 1rem 0.85rem 5.5rem`), dan muncul **Bottom Navigation Bar** tetap di bawah layar.
- **Landing Page**: Container terpusat `max-width: 1180px`, navbar sticky translucent (`rgba(243, 246, 243, 0.88)` + `backdrop-filter: blur(12px)`).

### 4.2 Spesifikasi Komponen Inti
- **Unboxed Card (`.card`)**: `border: none`, `border-radius: 0.85rem`, `padding: 1.25rem 1.5rem`, `box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.02)`.
- **Touch-Friendly Controls**:
  - Tombol (`.btn`): `min-height: 42px`, `border-radius: 0.5rem`, `padding: 0.65rem 1.15rem` (`.btn-sm`: `min-height: 36px`).
  - Input Form (`.form-control`): `min-height: 44px`, `border-radius: 0.5rem`, `border: 1px solid #cbd8ce`, fokus ring `box-shadow: 0 0 0 3px #eaf1eb`.
- **Modal Dialog & Slide-Over Panel**:
  - Desktop: centered modal (`max-width: 540px`, `border-radius: 1rem`) atau slide-over kanan (`max-width: 660px`, `height: 100vh`).
  - Mobile (`<= 768px`): modal otomatis menjadi *bottom sheet* (`border-radius: 1.25rem 1.25rem 0 0`, `max-height: 92vh`).
- **Stepper Progres**: Lingkaran bernomor `2.25rem` terhubung garis `#dbe4dc` (`3px`), mendukung state `default`, `active` (ring `#eaf1eb`), dan `completed` (isi solid `#446850`).
- **Pagination**: Tombol bernomor & prev/next dengan proteksi CSS defensif terhadap ikon SVG agar tetap proporsional (`1rem x 1rem`).

### 4.3 Motion & Micro-Interaction
- **Page Entry**: `pageFadeIn` (`280ms`, `cubic-bezier(0.16, 1, 0.3, 1)`, `translateY(4px)` -> `none`).
- **Page Loader Overlay**: Spinner melingkar `2.25rem` (`0.75s` linear/ease spin) dalam kartu putih `border-radius: 1.15rem` di atas backdrop blur.
- **Scroll Reveal (Landing)**: `.scroll-fade` dengan `IntersectionObserver` (`threshold: 0.12`, `0.6s`) dan dukungan penuh `@media (prefers-reduced-motion: reduce)`.

---

## 5. Arsitektur Perangkat Lunak Lengkap

### 5.1 Stack & Runtime
- **Backend**: Laravel 12 (`^12.0`), PHP `^8.2`.
- **Database**: MySQL (relasional dengan foreign key & index).
- **Frontend**: Server-rendered Blade + CSS Design System terpusat + JavaScript vanilla minimal (modal, tab filter, loader, dynamic exclusion). Tailwind CSS 4 + Vite 6 tersedia untuk pengembangan aset modern.
- **PDF Engine**: `dompdf/dompdf` (`^3.1`) dengan embedding asset (`logo`, `ttd`) sebagai **Base64 Data URI** agar bebas ketergantungan HTTP eksternal saat render.
- **Testing**: PHPUnit 11 (`RefreshDatabase` pada database uji terpisah).

### 5.2 Struktur Direktori & Lapisan Aplikasi

```text
app/
├── Enums/                    # Backed Enums (UserRole, StatusPengajuan, StatusPenugasanDosen)
├── Http/
│   ├── Controllers/          # Dikelompokkan per aktor/domain (Auth, Mahasiswa, Dosen, Kaprodi, Admin/Master)
│   ├── Middleware/           # Guard akses berbasis peran (mis. admin_utama)
│   └── Requests/             # FormRequest untuk validasi & otorisasi HTTP
├── Models/                   # Eloquent Models, casts Enum/Date, relasi domain & helper state
└── Services/                 # Layanan bisnis & utilitas (SuratUndanganService, MahasiswaCsvImporter)

resources/views/
├── layouts/app.blade.php     # Shell utama, CSS tokens, sidebar, topbar, bottom-nav, modal & loader
├── components/               # Komponen reusable (mis. portal-pagination)
├── pdf/                      # Template surat resmi A4 ber-KOP untuk Dompdf
└── [role]/[fitur]/           # Halaman Blade per peran & modul
```

### 5.3 Pola Desain Kunci (Siap Direplikasi ke Program Pertanian Lain)

1. **Backed Enums sebagai Kontrak UI + Domain**:
   Setiap enum status/role (contoh: [StatusPengajuan.php](file:///c:/Coding/laravel/project_fpst/app/Enums/StatusPengajuan.php) dan [UserRole.php](file:///c:/Coding/laravel/project_fpst/app/Enums/UserRole.php)) menyimpan tidak hanya nilai database, tetapi juga method `label()` dan `badgeClass()` sehingga penamaan dan pewarnaan status konsisten di seluruh tampilan.
2. **Relasi Penugasan Polimorfik (`penugasan_dosen`)**:
   Tabel [2026_09_04_000002_create_penugasan_dosen_table.php](file:///c:/Coding/laravel/project_fpst/database/migrations/2026_09_04_000002_create_penugasan_dosen_table.php) memakai `morphs('assignable')`. Pola ini sangat fleksibel untuk aplikasi pertanian lain — misalnya penugasan **Penyuluh Lapangan**, **Surveyor Lahan**, **Tim Verifikasi Benih/Pupuk**, atau **Pendamping Kelompok Tani** dengan mekanisme terima/tolak + alasan + rekomendasi pengganti.
3. **Arsip Surat & Dokumen Berversi (`surat`)**:
   Setiap pembaruan surat/SK tidak menimpa histori lama, melainkan menaikkan `versi` dan menandai versi sebelumnya `diperbarui`, sementara berkas PDF disimpan di disk privat (`storage/app/private`).
4. **Antrean FIFO & Filter Lintas Unit**:
   Halaman administrasi memakai antrean *First-In First-Out* (`oldest()`) dengan filter per unit/prodi, pencarian, dan badge jumlah antrean tertunda agar petugas lapangan/admin memproses pengajuan secara adil dan berurutan.
5. **Audit Trail & Notifikasi Internal Terintegrasi**:
   Helper statis `AktivitasLog::catat()` mencatat pelaku, aksi, deskripsi, dan IP; `Notifikasi::kirim()` / `kirimKePengelola()` mendistribusikan notifikasi terarah ke aktor terkait tanpa bergantung pada layanan eksternal.
6. **Batch CSV Importer Toleran Format**:
   [MahasiswaCsvImporter.php](file:///c:/Coding/laravel/project_fpst/app/Services/MahasiswaCsvImporter.php) mendeteksi UTF-8 BOM, delimiter `,` maupun `;` (standar Excel Indonesia), memetakan header fleksibel, dan melaporkan error per baris tanpa menggagalkan data valid.

### 5.4 Checklist Standar Keamanan & Skalabilitas Saat Replikasi
Saat mengadaptasi arsitektur ini ke proyek pertanian baru, pastikan 6 standar berikut langsung diterapkan sejak awal:
1. **Otorisasi Unduhan Berkas**: Setiap route download/preview berkas privat wajib memeriksa *Policy/kepemilikan* di database dan membersihkan path traversal (`..`), jangan hanya mengandalkan `base64_decode($path)`.
2. **Middleware Role & Scope Unit di Level Route**: Pasang middleware role pada setiap grup route dan validasi cakupan unit/wilayah/prodi di Policy.
3. **Validasi Status di `store()` + `DB::transaction` + `lockForUpdate()`**: Cegah *double submit*, penimpaan status aktif, dan *race condition* pada kuota atau penomoran surat.
4. **TTD / Stempel Resmi di Storage Privat**: Simpan gambar tanda tangan pejabat di disk `local` (privat), nonaktifkan `isRemoteEnabled` pada Dompdf, dan tambahkan QR Code verifikasi keaslian surat.
5. **Pisahkan Logika Bisnis ke `Action` / `Service`**: Jaga agar Controller tetap tipis (`FormRequest` -> `Action` -> `Policy` -> `Controller`).
6. **Kesiapan Peak Load**: Gunakan **Redis** untuk `SESSION_DRIVER` dan `CACHE_STORE` serta **Queue Worker** untuk pembuatan PDF dan pengiriman notifikasi massal.
