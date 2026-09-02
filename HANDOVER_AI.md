# Handover Proyek FPST untuk Diskusi dan Kelanjutan oleh AI

## 1. Tujuan dokumen

Dokumen ini adalah konteks kerja untuk AI atau pengembang berikutnya. Isinya disusun dari repository dan environment lokal pada **5 Agustus 2026 (Asia/Jakarta)**, bukan dari rencana yang belum diterapkan.

Urutan sumber kebenaran saat ada perbedaan:

1. Migration dan constraint database.
2. Action, policy, request, controller, dan model.
3. Test otomatis.
4. Dokumen ini.
5. `README.md` bawaan Laravel; saat ini belum mendeskripsikan aplikasi.

Sebelum mengubah kode, AI berikutnya harus menjalankan:

```powershell
git status --short --branch
git log -5 --oneline --decorate
php artisan test
```

Jangan menganggap seluruh alur selesai hanya karena semua test lulus. Beberapa status lanjutan hanya disiapkan langsung oleh test dan belum mempunyai action atau route produksi.

## 2. Snapshot repository

| Item | Nilai |
|---|---|
| Lokasi lokal | `C:\Coding\laravel\project_fpst` |
| Branch | `main` |
| HEAD | `aaac850` — `feat: combine supervisor consent workflow` |
| Sinkronisasi saat audit | `main...origin/main`, tanpa selisih |
| Status awal saat audit | Bersih; tidak ada staged, modified, atau untracked file |
| Commit terbaru | 5 Agustus 2026 11:27:41 WIB |
| Framework | Laravel 12 |
| PHP requirement | `^8.2` |
| PHP lokal | 8.4.20 |
| Database | MySQL |
| Test database | `project_fpst_testing` |
| Frontend | Blade, Tailwind CSS 4, Vite 6, JavaScript minimal |
| PDF | `dompdf/dompdf ^3.1` |
| Test runner | PHPUnit 11 melalui `php artisan test` |

Riwayat commit yang ada:

```text
aaac850  2026-08-05  feat: combine supervisor consent workflow
5243112  2026-08-05  test: expand seeded role accounts
a98b4c1  2026-08-05  feat: add role dashboards and workflow forms
94d0277  2026-08-05  Initial commit: build FPST thesis workflow system
```

Repository masih sangat muda: seluruh implementasi berada dalam empat commit tersebut.

## 3. Ringkasan sistem

Aplikasi mengelola alur skripsi lintas program studi:

1. Mahasiswa mengajukan judul.
2. Kaprodi menerima atau menolak judul.
3. Kaprodi menetapkan calon Pembimbing 1 dan opsional Pembimbing 2.
4. Petugas menerbitkan surat kesediaan.
5. Mahasiswa mengunggah hasil konsultasi offline dengan calon pembimbing.
6. Kaprodi atau admin memverifikasi hasil konsultasi.
7. Jika calon menolak, Kaprodi menetapkan pengganti pada siklus berikutnya.
8. Setelah seluruh calon aktif menerima, Kaprodi memfinalisasi pembimbing.
9. Petugas menerbitkan SK bimbingan.
10. Mahasiswa mengajukan seminar, petugas memverifikasi, menjadwalkan, dan menerbitkan surat.
11. Mahasiswa mengajukan sidang, petugas memverifikasi, menjadwalkan, dan menerbitkan surat.

Langkah 1–9 mempunyai action, route, policy, UI, dan test. Langkah 10–11 tersedia dari tahap pengajuan sampai penerbitan surat, tetapi pemicu status prasyarat dan penyelesaian acara belum tersedia. Detail gap ada di bagian 15.

## 4. Teknologi dan konfigurasi

### Backend

- Laravel 12, PHP `^8.2`.
- Eloquent ORM dan backed enum PHP untuk status domain.
- MySQL digunakan oleh aplikasi dan test.
- Session, cache, dan queue lokal dikonfigurasi memakai database.
- Mail lokal memakai driver `log`.
- File akademik disimpan pada disk `local`, yang mengarah ke `storage/app/private`.
- PDF dirender dengan Dompdf.
- Tidak ada API; semua endpoint saat ini adalah web route berbasis session dan CSRF.

### Frontend

- Server-rendered Blade.
- Tailwind CSS 4 melalui plugin Vite.
- JavaScript hanya bootstrap dasar; logika workflow berada di server.
- `node_modules` belum ada pada snapshot audit.
- `package-lock.json` belum ada. Instalasi frontend belum reproducible secara ketat.
- Node lokal 22.22.0 dan npm 10.9.4.
- Di PowerShell lokal, `npm.ps1` diblokir execution policy. Gunakan `cmd /c npm ...` bila masalah yang sama muncul.

### Environment lokal yang terdeteksi

```dotenv
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
APP_LOCALE=en
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=project_fpst
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
MAIL_MAILER=log
```

Nilai `APP_NAME`, locale, dan metadata aplikasi masih default Laravel. Jangan menyalin `APP_KEY` atau kredensial dari `.env` ke dokumentasi atau commit.

## 5. Menjalankan proyek

### Prasyarat

- PHP 8.2 atau lebih baru beserta ekstensi yang diminta Composer.
- Composer.
- MySQL aktif.
- Node.js dan npm.
- Database lokal `project_fpst` dan database test terpisah `project_fpst_testing`.

### Instalasi baru

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
cmd /c npm install
cmd /c npm run build
php artisan serve
```

Catatan:

- Buat database MySQL sebelum migration.
- `phpunit.xml` memaksa koneksi test ke `project_fpst_testing`, user `root`, password kosong, host `127.0.0.1`, port `3306`.
- Test memakai `RefreshDatabase`; jangan arahkan test ke database berisi data yang perlu dipertahankan.
- `storage:link` tidak diperlukan untuk dokumen akademik karena file sengaja berada pada disk privat dan hanya diunduh lewat controller ber-policy.
- Untuk development terpadu tersedia `composer dev`; skrip itu menjalankan server, queue listener, Pail, dan Vite dengan `npx concurrently`.

### Validasi yang sudah dijalankan pada snapshot ini

```text
composer validate --no-check-publish  -> berhasil
composer check-platform-reqs          -> berhasil
php artisan migrate:status            -> semua 18 migration berstatus Ran
php artisan route:list --except-vendor-> 40 route aplikasi
php artisan test                      -> 234 passed, 1209 assertions, 21.99s
```

Build frontend belum dijalankan karena `node_modules` tidak tersedia. Ini bukan bukti build gagal; statusnya **belum diverifikasi**.

## 6. Struktur kode

| Lokasi | Fungsi |
|---|---|
| `app/Actions` | Use case dan transisi status. Ini pusat aturan bisnis. |
| `app/Enums` | Nilai status, role, keputusan, jenis surat, dan jenis dokumen. |
| `app/Http/Controllers` | Adapter HTTP tipis; memanggil request dan action. |
| `app/Http/Requests` | Otorisasi request dan validasi input HTTP. |
| `app/Models` | Relasi Eloquent, casts enum/datetime, dan perlindungan mass assignment. |
| `app/Policies` | Otorisasi per resource dan program studi. |
| `app/Queries` | Query daftar pengajuan Kaprodi dan pencarian calon pembimbing. |
| `app/Services` | Audit, integritas file, PDF, signature, cakupan portal, notification adapter, dan arsip. |
| `database/migrations` | Skema kanonis dan constraint. |
| `database/seeders/RoleUserSeeder.php` | 22 akun demo lokal yang idempotent. |
| `resources/views/portal` | Dashboard dan halaman workflow lintas role. |
| `resources/views/pdf` | Template PDF surat. |
| `routes/web.php` | Seluruh route aplikasi. |
| `tests/Feature` | Mayoritas kontrak domain, HTTP, UI, policy, schema, dan keamanan file. |

Pola implementasi yang harus dipertahankan:

```text
HTTP request -> FormRequest -> Controller tipis -> Action -> Policy/Gate
                                              -> transaksi + row lock
                                              -> audit + arsip privat
```

Controller tidak boleh menerima `status`, identitas verifikator, penanda tangan, NIM, atau metadata file dari client. Nilai tersebut diturunkan dari user login, relasi database, waktu server, dan hasil validasi.

## 7. Role dan model otorisasi

Enum `UserRole` hanya memiliki:

- `mahasiswa`
- `dosen`
- `admin_prodi`
- `admin_utama`

**Kaprodi bukan role terpisah.** Kaprodi adalah user ber-role `dosen` yang profil dosennya direferensikan oleh `program_studi.ketua_prodi_id`. Jangan menambah pengecekan seperti `role === kaprodi` tanpa perubahan desain yang eksplisit.

### Matriks kemampuan utama

| Aktor | Cakupan data | Kemampuan utama |
|---|---|---|
| Mahasiswa | Data dengan NIM miliknya | Ajukan/perbaiki judul; unduh surat milik sendiri; unggah hasil konsultasi; ajukan seminar dan sidang ketika status memenuhi syarat. |
| Dosen biasa | Skripsi tempat ia menjadi calon, pembimbing, atau penguji | Melihat data akademik terkait; mengunduh surat kesediaannya. Tidak memverifikasi alur administratif. |
| Kaprodi | Program studi yang dipimpin | Putuskan judul; tetapkan/ganti/finalisasi pembimbing; unggah tanda tangan; seluruh operasi administratif prodi terkait. |
| Admin Prodi | Program studi pada pivot `user_program_studi` | Memantau judul; verifikasi dokumen; terbitkan surat tanpa tanda tangan Kaprodi; verifikasi dan jadwalkan seminar/sidang. Tidak memutuskan judul atau memfinalisasi pembimbing. |
| Admin Utama | Global | Operasi administratif global dan log aktivitas. Tidak bertindak sebagai Kaprodi; surat yang diterbitkan tetap unsigned. |

Aturan penting:

- Admin utama tidak otomatis boleh mengambil keputusan yang khusus Kaprodi.
- Admin Prodi dapat dipetakan ke lebih dari satu program studi.
- Dosen biasa tidak memperoleh akses seluruh data program studinya.
- Policy dan query cakupan harus sama-sama diterapkan: query mencegah kebocoran daftar, policy melindungi resource langsung.
- Login dibatasi setelah lima percobaan gagal berdasarkan kombinasi email ternormalisasi dan alamat IP.

## 8. Model data dan invariant database

### Relasi inti

```text
users
  |-- 0..1 mahasiswa -- program_studi
  |-- 0..1 dosen ------ program_studi
  `-- M..N program_studi melalui user_program_studi untuk Admin Prodi

mahasiswa -- 0..1 pengajuan_judul -- 0..1 skripsi
skripsi --- 1..N kesediaan_bimbingan
skripsi --- 0..1 seminar
skripsi --- 0..1 sidang_skripsi

surat             -- polymorphic -> kesediaan_bimbingan|skripsi|seminar|sidang_skripsi
dokumen_pengajuan -- polymorphic -> kesediaan_bimbingan|seminar|sidang_skripsi
notifikasi_log    -- polymorphic -> subjek yang dinotifikasi
aktivitas_log     -- polymorphic snapshot before/after
```

### Constraint penting

- Satu pengajuan judul per NIM: `pengajuan_judul.nim` unik.
- Satu skripsi per pengajuan dan per NIM.
- Satu seminar dan satu sidang per skripsi; pengajuan ulang memakai record yang sama.
- Satu kesediaan per kombinasi `skripsi_id + peran + siklus`.
- `siklus > 0`.
- Versi surat unik per subjek, jenis, dan versi; `versi > 0`.
- Versi dokumen unik per subjek, jenis, dan versi; `versi > 0`.
- Hash file harus SHA-256 lowercase 64 karakter.
- Nomor surat unik.
- Foreign key akademik mayoritas memakai `restrictOnDelete` untuk menjaga riwayat.
- `pengajuan_judul.judul` disalin ke `skripsi.judul` sebagai snapshot. Perubahan pengajuan setelah itu tidak boleh diam-diam mengubah snapshot.
- Model sensitif membatasi `$fillable`; metadata status/arsip biasanya ditulis dengan `forceFill` atau `forceCreate` hanya di action tepercaya.

### Catatan migration penting

Migration `2026_08_04_000001_add_role_to_users_table.php` sengaja melempar exception jika tabel `users` sudah berisi data. Migration ini aman untuk database baru, tetapi **tidak dapat langsung diterapkan pada instalasi legacy berisi user**. Sebelum migrasi legacy, buat pemetaan role dan strategi backfill eksplisit.

## 9. State machine domain

### Pengajuan judul

```text
diajukan --terima Kaprodi--> diverifikasi
    |
    `--tolak Kaprodi--> ditolak --perbaiki mahasiswa--> diajukan
```

Aturan:

- Mahasiswa hanya memiliki satu record pengajuan; perbaikan tidak membuat record baru.
- Alasan wajib saat ditolak.
- Keputusan hanya dapat diambil ketika status `diajukan`.
- Kaprodi harus memimpin program studi mahasiswa.

### Skripsi

Enum mendefinisikan:

```text
menunggu_kesediaan_pembimbing
  -> bimbingan_aktif
  -> siap_seminar
  -> siap_sidang
  -> selesai
```

Transisi yang benar-benar diimplementasikan hanya:

```text
menunggu_kesediaan_pembimbing --finalisasi Kaprodi--> bimbingan_aktif
```

Transisi ke `siap_seminar`, `siap_sidang`, dan `selesai` belum mempunyai action/route produksi.

### Kesediaan pembimbing

```text
ditunjuk
  -> menunggu_upload        (setelah surat diterbitkan)
  -> menunggu_verifikasi    (setelah mahasiswa upload)
  -> diterima               (dokumen valid + calon bersedia)
  -> ditolak                (dokumen valid + calon tidak bersedia)

menunggu_verifikasi
  -> upload_tidak_valid
  -> menunggu_verifikasi    (upload versi baru)

ditolak
  -> calon baru pada siklus + 1, status ditunjuk
  -> surat calon baru otomatis diterbitkan oleh GantiCalonPembimbing
```

Enum juga memiliki `surat_terbit` dan `dibatalkan`, tetapi alur aktif saat ini menerbitkan surat lalu langsung menyimpan `menunggu_upload`. Riwayat siklus lama dipertahankan.

Keputusan calon tidak dilakukan langsung oleh akun dosen. Proses saat ini mengasumsikan konsultasi offline: mahasiswa mengunggah bukti, lalu Kaprodi/admin memverifikasi hasilnya.

### Dokumen pengajuan

```text
diunggah | menunggu_verifikasi -> terverifikasi
                                 -> ditolak
                                 -> dibatalkan ketika versi baru menggantikan versi aktif
```

Untuk hasil konsultasi, hanya upload terbaru yang sedang menunggu verifikasi yang dapat diputuskan. File terverifikasi tidak dapat diganti.

### Seminar dan sidang

Keduanya memakai pola:

```text
diajukan -> diverifikasi -> dijadwalkan -> selesai
    `--------> ditolak -> diajukan ulang pada record sama
```

Action tersedia untuk `diajukan`, `diverifikasi`/`ditolak`, dan `dijadwalkan`. Tidak ada action untuk `selesai`.

Prasyarat pengajuan:

- Seminar: skripsi harus `siap_seminar`.
- Sidang: skripsi harus `siap_sidang` dan seminar harus `selesai`.
- File seminar/sidang saat ini opsional.
- Saat ditolak, pengajuan ulang memakai record seminar/sidang yang sama dan file baru mendapat versi berikutnya.

### Surat

```text
draft | diterbitkan | terverifikasi -> dibatalkan ketika versi baru terbit
```

Jenis surat:

- `kesediaan_pembimbing`
- `sk_bimbingan`
- `undangan_seminar`
- `surat_tugas_seminar`
- `undangan_sidang`
- `surat_tugas_sidang`

Surat baru tidak menimpa file lama. Versi lama berstatus `dibatalkan`, tetapi arsip file tetap ada.

## 10. Alur yang sudah diimplementasikan

### A. Pengajuan dan verifikasi judul

File utama:

- `app/Actions/PengajuanJudul/AjukanJudul.php`
- `app/Actions/PengajuanJudul/AjukanUlangJudul.php`
- `app/Actions/PengajuanJudul/TerimaJudul.php`
- `app/Actions/PengajuanJudul/TolakJudul.php`
- `app/Policies/PengajuanJudulPolicy.php`
- `resources/views/mahasiswa/pengajuan-judul/index.blade.php`
- `resources/views/kaprodi/pengajuan-judul/*`

Judul dinormalisasi dengan `Str::squish`, maksimal 1000 karakter pada request. NIM, status, verifikator, dan waktu keputusan selalu berasal dari server.

### B. Penetapan calon pembimbing

`TetapkanCalonPembimbing`:

- Hanya Kaprodi terkait.
- Hanya untuk judul `diverifikasi`.
- Pembimbing 1 wajib; Pembimbing 2 opsional.
- Calon harus berbeda dan berasal dari prodi mahasiswa.
- Membuat satu `skripsi` beserta snapshot judul.
- Membuat record `kesediaan_bimbingan` siklus 1.
- Belum mengisi `skripsi.pembimbing1_id/pembimbing2_id`; field final baru diisi saat finalisasi.
- Double submit ditolak dan dilindungi unique constraint.

Pencarian calon dilakukan server-side, dibatasi prodi, maksimal 20 hasil, dan mendukung penyertaan calon yang sudah dipilih.

### C. Surat kesediaan dan hasil konsultasi

- Penerbitan awal surat dilakukan melalui tombol administratif untuk setiap calon berstatus `ditunjuk`.
- Jika penerbit Kaprodi, PDF memakai tanda tangan privat yang telah diunggah. Identitas signer harus konsisten dengan Kaprodi aktif.
- Jika penerbit Admin Prodi/Admin Utama, PDF diterbitkan tanpa signature.
- Setelah surat terbit, status kesediaan menjadi `menunggu_upload`.
- Mahasiswa dapat mengunduh satu PDF gabungan untuk surat Pembimbing 1 dan 2 yang tersedia.
- Dosen hanya dapat melihat dan mengunduh surat kesediaan miliknya.
- Mahasiswa mengunggah hasil konsultasi PDF/JPG/JPEG/PNG maksimal 5 MB.
- Verifikator memilih `valid_bersedia`, `valid_tidak_bersedia`, atau `upload_tidak_valid`.
- Catatan wajib untuk dua keputusan selain `valid_bersedia`.
- Penolakan calon memungkinkan Kaprodi membuat pengganti pada peran yang sama, siklus berikutnya. Penggantian otomatis menerbitkan surat calon baru dalam transaksi yang sama.

### D. Finalisasi dan SK bimbingan

Finalisasi hanya berhasil jika:

- Judul masih terverifikasi dan sama dengan snapshot skripsi.
- Tidak ada siklus kesediaan aktif yang belum diputuskan.
- Siklus terbaru Pembimbing 1 diterima.
- Jika pernah ada Pembimbing 2, siklus terbarunya juga harus diterima.
- Hasil kesediaan dan dokumen terbaru sudah diverifikasi sah.
- Calon final tetap berasal dari prodi mahasiswa dan tidak duplikat.

Finalisasi mengisi pembimbing final dan mengubah skripsi ke `bimbingan_aktif`. Eksekusi ulang dengan hasil identik bersifat idempotent.

SK bimbingan dapat diterbitkan ketika skripsi `bimbingan_aktif` dan pembimbing final lengkap sesuai kontrak. Versi baru mengarsipkan versi lama. Kaprodi menandatangani; admin menerbitkan versi unsigned.

### E. Seminar

- Mahasiswa pemilik dapat mengajukan ketika skripsi `siap_seminar`.
- Berkas opsional, privat, maksimal 5 MB.
- Kaprodi/Admin Prodi/Admin Utama dalam scope dapat menerima atau menolak.
- Penolakan wajib memiliki catatan.
- Pengajuan diterima dapat dijadwalkan dengan dua penguji berbeda dari prodi yang sama, waktu, dan tempat.
- Submit jadwal identik bersifat idempotent.
- Setelah `dijadwalkan`, petugas dapat menerbitkan undangan dan surat tugas seminar.
- Belum ada fitur pencatatan hasil atau penandaan seminar selesai.

### F. Sidang

- Mahasiswa pemilik dapat mengajukan ketika skripsi `siap_sidang` dan seminar `selesai`.
- Pola validasi, verifikasi, penjadwalan, file privat, versi, dan surat sama dengan seminar.
- Tersedia undangan dan surat tugas sidang.
- Belum ada fitur pencatatan hasil, revisi, nilai, kelulusan, atau penandaan sidang/skripsi selesai.

## 11. Route dan halaman UI

Seluruh route mutasi berada dalam middleware `auth` dan form Blade memakai CSRF. Route penting:

| Area | Route |
|---|---|
| Auth | `/login`, `/logout` |
| Dashboard | `/dashboard` |
| Profil | `/profil` |
| Pengajuan mahasiswa | `/mahasiswa/pengajuan-judul` |
| Verifikasi judul Kaprodi | `/kaprodi/pengajuan-judul` |
| Portal lintas role | `/portal/pengajuan-judul`, `/portal/skripsi`, `/portal/seminar`, `/portal/sidang`, `/portal/surat` |
| Audit global | `/portal/log-aktivitas` |
| Tanda tangan Kaprodi | `POST /kaprodi/tanda-tangan` |
| Download privat | `/surat/{surat}/download`, `/dokumen-pengajuan/{dokumen}/download` |
| Surat kesediaan gabungan | `/skripsi/{skripsi}/surat-kesediaan/download` |

`php artisan route:list --except-vendor` menampilkan 40 route aplikasi. Health route Laravel juga dikonfigurasi pada `/up`.

Dashboard tersedia untuk mahasiswa, dosen, Kaprodi, Admin Prodi, dan Admin Utama. Layout menu menyesuaikan role. Portal memakai pagination; daftar pengajuan Kaprodi dipaginasi 15 record per halaman dan mendukung filter status serta pencarian.

## 12. Keamanan, integritas, dan audit

### File privat

- Semua dokumen akademik, PDF surat, dan tanda tangan disimpan di `storage/app/private`.
- Download selalu melewati controller dan policy.
- Path file dibentuk server-side dari subjek, versi, hash, dan extension tervalidasi.
- Dokumen tidak boleh menimpa path yang sudah ada.
- Hash SHA-256 diverifikasi saat download atau sebelum keputusan yang bergantung pada file.
- Jika database transaction rollback setelah file ditulis, action berusaha menghapus file orphan.

### Validasi upload

- Dokumen: PDF, JPG/JPEG, atau PNG; maksimal 5 MB.
- Validator memeriksa MIME, extension, dan magic bytes, bukan extension saja.
- Tanda tangan: PNG atau JPEG valid; maksimal 2 MB di FormRequest.
- Tanda tangan lama dihapus setelah referensi database berhasil berpindah ke file baru. Tanda tangan tidak versioned seperti surat.

### Concurrency dan idempotensi

- Action kritis memakai `DB::transaction` dan `lockForUpdate`.
- Unique constraint menjadi lapisan terakhir untuk double submit.
- Beberapa transaksi mencoba ulang maksimal tiga kali.
- Finalisasi dan penyimpanan jadwal identik memiliki perilaku idempotent.

### Audit

`CatatAktivitas` menyimpan:

- user pelaku;
- tipe dan ID subjek;
- nama aksi;
- snapshot `before_data`/`after_data` terpilih;
- IP untuk request web;
- waktu server.

Audit ikut transaksi bisnis sehingga ikut rollback bila operasi utama gagal. Snapshot tidak menyimpan bytes, path, atau isi file sensitif.

## 13. Notifikasi

Fondasi notifikasi ada, tetapi belum menjadi fitur aktif:

- Contract: `app/Contracts/PengirimNotifikasi.php`.
- Service idempotent: `app/Services/Notification/KirimNotifikasi.php`.
- Log: `notifikasi_log` dengan status `terkirim` atau `gagal`.
- Binding default: `PengirimTidakTersedia`, yang selalu melempar exception.
- Service menangkap kegagalan provider dan mencatat `gagal` tanpa menggagalkan transaksi akademik.
- Tidak ditemukan pemanggilan `KirimNotifikasi` dari action workflow.

Kesimpulan: schema dan mekanisme retry/duplikasi sudah ditest, tetapi belum ada channel, provider nyata, template, recipient resolver, queue job, atau trigger workflow.

## 14. Data lokal saat handover

Database lokal `project_fpst` bukan database kosong. Hitungan saat audit:

| Entitas | Jumlah |
|---|---:|
| User | 22 |
| Program studi | 2 |
| Mahasiswa | 8 |
| Dosen, termasuk Kaprodi | 8 |
| Pengajuan judul | 1 |
| Skripsi | 1 |
| Seminar | 0 |
| Sidang | 0 |
| Surat | 2 |
| Dokumen pengajuan | 2 |
| Aktivitas log | 10 |

Record workflow lokal yang ada:

- Mahasiswa: Rizky Maulana, NIM `222000000001`.
- Pengajuan ID 3 berstatus `diverifikasi`.
- Skripsi ID 3 berstatus `bimbingan_aktif`.
- Pembimbing final: NIDN `1000000008` dan `1000000002`.
- Dua kesediaan siklus 1 berstatus `diterima`/`bersedia`.
- Dua surat kesediaan versi 1 berstatus `diterbitkan`, unsigned.
- Dua hasil konsultasi versi 1 berstatus `terverifikasi`.
- Belum ada SK bimbingan, seminar, atau sidang pada data lokal ini.
- Judul record berasal dari data Faker/manual QA dan bukan data demonstrasi yang layak dipresentasikan.

ID database tidak berurutan karena database telah dipakai untuk pengujian/manual QA. Jangan menulis test atau fitur yang bergantung pada ID tertentu.

### Akun demo

Seluruh akun yang dibuat `RoleUserSeeder` memakai password lokal:

```text
password
```

Daftar email:

| Kategori | Email |
|---|---|
| Kaprodi TI | `kaprodi.ti@example.test` |
| Kaprodi SI | `kaprodi.si@example.test` |
| Dosen TI/SI | `dosen@example.test` |
| Dosen TI | `dosen.ti2@example.test` |
| Dosen TI | `dosen.ti3@example.test` |
| Dosen TI | `dosen.ti4@example.test` |
| Dosen SI | `dosen.si2@example.test` |
| Dosen SI | `dosen.si3@example.test` |
| Mahasiswa TI | `mahasiswa.ti1@example.test` |
| Mahasiswa TI | `mahasiswa.ti2@example.test` |
| Mahasiswa TI | `mahasiswa.ti3@example.test` |
| Mahasiswa TI | `mahasiswa.ti4@example.test` |
| Mahasiswa TI | `mahasiswa.ti5@example.test` |
| Mahasiswa SI | `mahasiswa.si@example.test` |
| Mahasiswa SI | `mahasiswa.si2@example.test` |
| Mahasiswa SI | `mahasiswa.si3@example.test` |
| Admin Prodi TI | `admin.prodi.ti@example.test` |
| Admin Prodi SI | `admin.prodi.si@example.test` |
| Admin Prodi gabungan | `admin.prodi.gabungan@example.test` |
| Admin Utama | `admin.utama@example.test` |
| Admin Utama | `admin.utama2@example.test` |
| Admin Utama | `admin.utama3@example.test` |

Seeder idempotent dan hanya membuat master data serta akun; seeder tidak membuat workflow skripsi.

## 15. Gap terverifikasi dan risiko

Urutan berikut menunjukkan dampak, bukan sekadar jumlah file yang belum dibuat.

### P0 — Alur tidak dapat diselesaikan end-to-end melalui UI/API

Belum ada action, controller, route, form, atau policy khusus untuk:

1. Mengubah skripsi `bimbingan_aktif -> siap_seminar`.
2. Menandai seminar `dijadwalkan -> selesai`.
3. Mengubah skripsi `siap_seminar -> siap_sidang`.
4. Menandai sidang `dijadwalkan -> selesai`.
5. Mengubah skripsi menjadi `selesai`.

Bukti: pencarian seluruh write status pada `app` hanya menemukan transisi skripsi ke `bimbingan_aktif`; referensi `StatusSeminar::Selesai` dan `StatusSkripsi::SiapSeminar/SiapSidang` sebagai write hanya muncul di test melalui factory/`forceFill`.

Dampak: pengguna produksi akan berhenti setelah SK bimbingan kecuali status diedit langsung di database.

Jangan langsung menambah tombol “Selesai” tanpa keputusan bisnis. Tentukan lebih dulu bukti bimbingan, aktor yang mengesahkan, hasil seminar/sidang, kebutuhan revisi, nilai, tanggal selesai, dan audit yang wajib disimpan.

### P0 — Aturan hasil seminar dan sidang belum dimodelkan

Saat ini tabel seminar/sidang hanya memiliki jadwal, penguji, status, catatan penolakan, dan verifikator pengajuan. Belum ada:

- hasil/lulus/tidak lulus;
- nilai atau berita acara;
- catatan/revisi;
- batas waktu revisi;
- pengesah hasil;
- dokumen hasil;
- riwayat penjadwalan ulang.

Skema harus diputuskan sebelum membuat transisi `selesai` agar status tidak kehilangan makna audit.

### P1 — Notifikasi belum terintegrasi

Provider default selalu gagal, tidak ada trigger dari action, dan tidak ada queue job. Tentukan channel dan recipient untuk setiap event sebelum menghubungkan service.

### P1 — Belum ada pengelolaan master data

Tidak ada UI/route CRUD untuk user, mahasiswa, dosen, program studi, ketua prodi, atau mapping Admin Prodi. Seluruhnya bergantung pada seeder/database. Belum ada import data institusi atau strategi sinkronisasi SIAKAD.

### P1 — Kesiapan produksi belum selesai

- `.env.example` masih memakai nama dan locale default Laravel.
- Tidak ada dokumentasi deployment, backup, restore, retention, atau rotasi file privat.
- Tidak ada CI workflow dalam repository.
- Tidak ada konfigurasi provider notifikasi.
- Tidak ada bukti uji browser lintas perangkat atau audit aksesibilitas.
- Template PDF ditest secara konten/integritas, tetapi belum ada artefak visual QA formal dalam repository.
- `APP_DEBUG=true` hanya pantas untuk lokal.

### P1 — Dependency frontend belum dikunci

`package-lock.json` tidak ada dan build belum diverifikasi pada snapshot ini. Buat lockfile melalui instalasi npm yang disepakati, jalankan build, lalu commit lockfile bila npm adalah package manager resmi.

### P1 — Migration legacy membutuhkan rencana khusus

Migration role berhenti bila user lama ada. Diperlukan migration/backfill terpisah untuk deployment ke database eksisting. Jangan menghapus guard tanpa pemetaan role yang tervalidasi.

### P2 — Aturan jadwal masih minimal

Request menerima nilai `date` tanpa aturan `after:now`. Belum ada pemeriksaan konflik ruangan, konflik penguji, jam kerja, zona waktu input, atau penjadwalan ulang. Putuskan kebutuhan operasional sebelum memperketat validasi.

### P2 — Berkas seminar dan sidang opsional

Kode dan UI secara eksplisit mengizinkan pengajuan tanpa file. Konfirmasi apakah ini memang aturan bisnis atau hanya kompromi implementasi awal.

### P2 — Dokumentasi proyek belum menggantikan README bawaan

`README.md` masih README Laravel standar. Setelah keputusan bisnis stabil, ganti dengan panduan proyek yang lebih pendek dari handover ini: tujuan, setup, test, akun demo, dan batas sistem.

## 16. Keputusan bisnis yang perlu dibahas

Pertanyaan berikut mengubah desain; AI tidak boleh menjawabnya dengan asumsi diam-diam.

1. Apa syarat objektif skripsi menjadi `siap_seminar`? Jumlah bimbingan, persetujuan pembimbing, atau upload dokumen tertentu?
2. Siapa yang menyatakan seminar selesai: admin, Kaprodi, ketua penguji, atau dosen pembimbing?
3. Hasil seminar apa saja: lulus, lulus dengan revisi, mengulang, atau ditolak?
4. Apa syarat skripsi menjadi `siap_sidang` setelah seminar?
5. Siapa yang menyatakan sidang selesai dan skripsi final?
6. Apakah nilai, berita acara, revisi, deadline, dan versi naskah final masuk scope?
7. Apakah berkas seminar/sidang wajib? Jika wajib, dokumen apa dan berapa banyak?
8. Apakah calon pembimbing seharusnya merespons langsung melalui akun dosen, atau bukti konsultasi offline tetap menjadi mekanisme resmi?
9. Apakah Pembimbing 2 benar-benar opsional untuk semua program studi?
10. Apakah format nomor surat saat ini sesuai tata naskah institusi?
11. Apakah signature berupa gambar memenuhi kebijakan institusi, atau perlu QR/verifikasi digital/sertifikat?
12. Channel notifikasi apa yang digunakan: email, WhatsApp, atau notifikasi internal?
13. Data master berasal dari input manual, import spreadsheet, atau integrasi SIAKAD?
14. Apakah Admin Utama boleh mengubah master data dan melakukan override workflow? Saat ini tidak ada override akademik.
15. Berapa lama dokumen, audit, dan surat versi lama harus disimpan?

## 17. Rekomendasi urutan kerja

### Tahap 1 — Tutup kontrak bisnis alur akhir

1. Jawab pertanyaan 1–7 pada bagian 16.
2. Definisikan state machine final dan aktor tiap transisi.
3. Tentukan data hasil seminar/sidang yang wajib diaudit.
4. Buat migration baru; jangan mengubah migration lama yang mungkin sudah dijalankan.

### Tahap 2 — Implementasikan alur akhir vertikal

Untuk setiap transisi baru:

1. Tambah enum hanya bila status baru benar-benar diperlukan.
2. Tambah migration dan constraint.
3. Tambah policy method.
4. Tambah FormRequest tanpa field status/verifikator bebas.
5. Tambah Action dengan transaction, `lockForUpdate`, validasi prasyarat, dan audit.
6. Tambah controller tipis dan route bernama.
7. Tambah UI hanya saat state relevan.
8. Tambah test schema, action, policy, HTTP, UI, concurrency/idempotensi, dan rollback.
9. Jalankan seluruh 234 test lama plus test baru.

Urutan transisi yang disarankan:

```text
bimbingan_aktif
  -> persetujuan siap seminar
  -> seminar selesai + hasil
  -> persetujuan siap sidang
  -> sidang selesai + hasil
  -> unggah/validasi naskah final
  -> skripsi selesai
```

### Tahap 3 — Operasional dan produksi

1. Master data dan import.
2. Provider notifikasi dan queue.
3. Nomor surat resmi dan signature policy.
4. Lockfile/build frontend.
5. CI untuk test dan build.
6. Dokumentasi deployment, backup, restore, dan retention.
7. Visual QA PDF dan browser QA lintas role.

## 18. Strategi test yang harus dipertahankan

Test suite saat ini kuat pada:

- schema dan foreign key;
- enum casts dan relasi;
- mass-assignment protection;
- scope lintas role/prodi;
- manipulasi input status/identitas;
- double submit dan idempotensi;
- rollback database dan cleanup file;
- versi immutable;
- file palsu, file hilang, dan hash berubah;
- N+1 query pada daftar/pencarian penting;
- visibility form berdasarkan status;
- login, rate limit, logout, dan redirect guest.

Saat menambah fitur, jangan mengganti test lama dengan test yang lebih longgar. Gunakan database `project_fpst_testing`. Perintah utama:

```powershell
php artisan test
php artisan test --filter NamaTest
```

Setelah dependency frontend tersedia:

```powershell
cmd /c npm run build
```

## 19. File prioritas untuk dibaca AI berikutnya

Mulai dari file berikut, bukan membaca seluruh repository secara acak:

1. `routes/web.php`
2. `app/Enums/StatusSkripsi.php`
3. `app/Enums/StatusSeminar.php`
4. `app/Enums/StatusSidangSkripsi.php`
5. `app/Models/User.php`
6. `app/Services/Portal/CakupanDataPortal.php`
7. `app/Actions/Skripsi/FinalisasiPembimbing.php`
8. `app/Actions/Seminar/AjukanSeminar.php`
9. `app/Actions/Seminar/VerifikasiSeminar.php`
10. `app/Actions/Seminar/JadwalkanSeminar.php`
11. `app/Actions/Sidang/AjukanSidang.php`
12. `app/Actions/Sidang/VerifikasiSidang.php`
13. `app/Actions/Sidang/JadwalkanSidang.php`
14. `resources/views/portal/seminar/index.blade.php`
15. `resources/views/portal/sidang/index.blade.php`
16. `tests/Feature/WorkflowFormsUiTest.php`
17. `tests/Feature/SeminarPengajuanVerifikasiTest.php`
18. `tests/Feature/SidangPengajuanVerifikasiTest.php`

Untuk aturan surat dan integritas file, lanjutkan ke:

- `app/Services/Surat/ArsipPdfSurat.php`
- `app/Services/Upload/ValidasiUploadPrivat.php`
- `app/Services/Document/PastikanIntegritasDokumen.php`
- `app/Policies/SuratPolicy.php`
- `app/Policies/DokumenPengajuanPolicy.php`

## 20. Instruksi kerja untuk AI berikutnya

Gunakan bagian ini sebagai prompt operasional bersama dokumen handover:

```text
Anda melanjutkan aplikasi Laravel 12 pengelolaan skripsi FPST.

1. Baca HANDOVER_AI.md dan file prioritas yang relevan.
2. Periksa git status dan commit aktif sebelum mengubah apa pun.
3. Perlakukan migration, action, policy, dan test sebagai sumber kebenaran.
4. Jangan menganggap enum berarti transisinya sudah diimplementasikan.
5. Jangan menerima status, NIM, verifikator, signer, hash, versi, path, atau scope prodi dari request client.
6. Pertahankan transaksi, row lock, audit, versi immutable, file privat, pemeriksaan hash, policy, dan cleanup file saat rollback.
7. Jangan mengubah migration lama; buat migration baru.
8. Jangan membuat keputusan bisnis tentang hasil seminar/sidang tanpa konfirmasi pengguna.
9. Implementasikan satu alur vertikal lengkap: schema -> policy -> request -> action -> controller/route -> UI -> test.
10. Jalankan seluruh test dan laporkan hasil faktual, termasuk bagian yang belum dapat diverifikasi.
```

## 21. Definition of done untuk pekerjaan berikutnya

Sebuah perubahan belum selesai hanya karena endpoint berhasil pada happy path. Minimal harus memenuhi:

- Aturan bisnis dan aktor transisi disetujui.
- Migration aman dan constraint relevan tersedia.
- Request tidak mempercayai metadata sensitif dari client.
- Policy menutup akses lintas role dan program studi.
- Action memakai transaksi/lock bila menyentuh state workflow.
- Audit menyimpan perubahan penting tanpa isi/path file sensitif.
- File privat tervalidasi dan diverifikasi hash jika ada.
- Double submit memiliki perilaku yang ditentukan.
- Rollback database tidak meninggalkan file orphan.
- UI hanya menawarkan aksi pada state yang benar.
- Test happy path, unauthorized, invalid transition, tampering, rollback, dan HTTP/UI lulus.
- `php artisan test` tetap hijau.
- Build frontend dijalankan bila asset berubah.
- Dokumentasi handover diperbarui jika status implementasi atau keputusan bisnis berubah.

## 22. Ringkasan satu paragraf

Fondasi aplikasi sudah kuat untuk autentikasi, scope multi-prodi, pengajuan judul, siklus kesediaan pembimbing, finalisasi, arsip surat/dokumen privat berversi, audit, seminar, dan sidang sampai penjadwalan serta penerbitan surat. Test suite saat ini lulus 234 test dengan 1209 assertion. Batas utama bukan kegagalan teknis pada fitur tersebut, melainkan lifecycle yang belum tersambung setelah `bimbingan_aktif`: tidak ada mekanisme produksi untuk membuat skripsi siap seminar, mencatat seminar selesai, membuat skripsi siap sidang, mencatat sidang selesai, dan menyelesaikan skripsi. Prioritas diskusi berikutnya adalah menetapkan aturan bisnis hasil seminar/sidang dan prasyarat tiap transisi, lalu mengimplementasikannya dengan pola action-policy-transaction-audit-test yang sudah digunakan repository.
