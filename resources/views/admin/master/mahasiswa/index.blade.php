@extends('layouts.app')

@section('title', 'Master Data Mahasiswa - FPST UPB')
@section('page_title', 'Master Data Mahasiswa')

@section('content')

@include('admin.master._nav')

<!-- Flash Alert Khusus CSV Errors jika ada -->
@if (session('csv_errors') && count(session('csv_errors')) > 0)
    <div class="card" style="margin-bottom: 1.25rem; border-left: 4px solid #f59e0b; background: #fffbeb; padding: 1rem;">
        <div style="font-weight: 700; color: #b45309; font-size: 0.9rem; margin-bottom: 0.5rem;">
            ⚠️ Catatan Baris CSV yang Dilewati:
        </div>
        <div style="max-height: 150px; overflow-y: auto; font-size: 0.82rem; color: #92400e;">
            <ul style="padding-left: 1.25rem; margin: 0;">
                @foreach (session('csv_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<!-- Stat Box -->
<div class="grid-stats" style="margin-bottom: 1.25rem;">
    <div class="stat-box">
        <div class="stat-box-title">Total Mahasiswa Terdaftar</div>
        <div class="stat-box-value">{{ $totalMahasiswa }}</div>
    </div>
    @foreach ($daftarProdi as $p)
        <div class="stat-box">
            <div class="stat-box-title">Mahasiswa {{ $p->kode ?: $p->nama }}</div>
            <div class="stat-box-value">{{ $p->users()->where('role', 'mahasiswa')->count() }}</div>
        </div>
    @endforeach
</div>

<div class="card" style="padding: 1.25rem;">
    <!-- Toolbar Aksi & Filter -->
    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
        <!-- Tombol Aksi Tambah & Import -->
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <button type="button" onclick="openModal('modal-tambah-mhs')" class="btn btn-primary btn-sm" style="height: 38px; display: inline-flex; align-items: center; gap: 0.35rem;">
                <span>➕</span> Tambah Mahasiswa
            </button>
            <button type="button" onclick="openModal('modal-import-csv')" class="btn btn-secondary btn-sm" style="height: 38px; display: inline-flex; align-items: center; gap: 0.35rem; background: #2f855a; color: #fff; border-color: #276749;">
                <span>📥</span> Import Batch (.CSV)
            </button>
            <a href="{{ route('admin.master.mahasiswa.template-csv') }}" class="btn btn-secondary btn-sm" style="height: 38px; display: inline-flex; align-items: center; gap: 0.35rem;">
                <span>📄</span> Unduh Template CSV
            </a>
        </div>

        <!-- Filter & Pencarian -->
        <form method="GET" action="{{ route('admin.master.mahasiswa.index') }}" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
            <select name="prodi_id" class="form-control" style="font-size: 0.82rem; height: 38px; width: 170px;" onchange="this.form.submit()">
                <option value="">Semua Prodi</option>
                @foreach ($daftarProdi as $prodi)
                    <option value="{{ $prodi->id }}" {{ $prodiFilter == $prodi->id ? 'selected' : '' }}>
                        {{ $prodi->kode ?: $prodi->nama }}
                    </option>
                @endforeach
            </select>

            <div style="position: relative; width: 220px;">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari Nama / NIM / Email..." class="form-control" style="font-size: 0.82rem; height: 38px; padding-left: 2rem;">
                <span style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: var(--text-muted);">🔍</span>
            </div>

            <button type="submit" class="btn btn-secondary btn-sm" style="height: 38px;">Cari</button>
            @if ($search || $prodiFilter)
                <a href="{{ route('admin.master.mahasiswa.index') }}" class="btn btn-secondary btn-sm" style="height: 38px;" title="Reset Filter">✕</a>
            @endif
        </form>
    </div>

    <!-- Tabel Data Mahasiswa -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th style="width: 140px;">NIM</th>
                    <th>Nama Mahasiswa</th>
                    <th style="width: 160px;">Program Studi</th>
                    <th>Kontak</th>
                    <th style="width: 150px;">Status Skripsi</th>
                    <th style="width: 130px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarMahasiswa as $index => $mhs)
                    <tr>
                        <td style="text-align: center; color: var(--text-muted); font-size: 0.82rem;">
                            {{ $daftarMahasiswa->firstItem() + $index }}
                        </td>
                        <td>
                            <strong style="color: #1c2b20; font-family: monospace; font-size: 0.9rem;">
                                {{ $mhs->nomor_induk }}
                            </strong>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #1c2b20;">{{ $mhs->name }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $mhs->email }}</div>
                        </td>
                        <td>
                            @if ($mhs->programStudi)
                                <span class="badge badge--primary" style="font-size: 0.75rem;">
                                    {{ $mhs->programStudi->nama }} ({{ $mhs->programStudi->kode }})
                                </span>
                            @else
                                <span class="badge badge--secondary" style="font-size: 0.75rem;">Belum diset</span>
                            @endif
                        </td>
                        <td style="font-size: 0.82rem;">
                            @if ($mhs->no_hp)
                                <div>📱 {{ $mhs->no_hp }}</div>
                            @else
                                <span style="color: var(--text-muted); font-style: italic;">-</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $skripsi = $mhs->pengajuanSkripsi;
                            @endphp
                            @if ($skripsi)
                                @if ($skripsi->status->value === 'selesai')
                                    <span class="badge badge--success" style="font-size: 0.72rem;">SK Terbit</span>
                                @elseif ($skripsi->status->value === 'diproses')
                                    <span class="badge badge--warning" style="font-size: 0.72rem;">Diproses</span>
                                @elseif ($skripsi->status->value === 'diajukan')
                                    <span class="badge badge--primary" style="font-size: 0.72rem;">Judul Diajukan</span>
                                @else
                                    <span class="badge badge--danger" style="font-size: 0.72rem;">{{ ucfirst($skripsi->status->value) }}</span>
                                @endif
                            @else
                                <span style="color: var(--text-muted); font-size: 0.78rem;">Belum Mengajukan</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 0.35rem; justify-content: center;">
                                <button type="button" 
                                    class="btn btn-secondary btn-sm" 
                                    style="padding: 0.25rem 0.5rem; font-size: 0.78rem;"
                                    title="Edit Mahasiswa"
                                    onclick="editMahasiswa({{ json_encode([
                                        'id' => $mhs->id,
                                        'name' => $mhs->name,
                                        'nomor_induk' => $mhs->nomor_induk,
                                        'email' => $mhs->email,
                                        'program_studi_id' => $mhs->program_studi_id,
                                        'no_hp' => $mhs->no_hp,
                                    ]) }})">
                                    ✏️ Edit
                                </button>

                                <form method="POST" action="{{ route('admin.master.mahasiswa.destroy', $mhs->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus mahasiswa {{ $mhs->name }} ({{ $mhs->nomor_induk }})?')" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.78rem; background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;" title="Hapus Mahasiswa">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            Tidak ada data mahasiswa ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.25rem;">
        {{ $daftarMahasiswa->links() }}
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: TAMBAH MAHASISWA (SATU PER SATU)                  -->
<!-- ======================================================== -->
<div id="modal-tambah-mhs" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div class="modal-dialog" style="background: #fff; border-radius: 0.75rem; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; margin: auto;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #1c2b20;">➕ Tambah Data Mahasiswa</h3>
            <button type="button" onclick="closeModal('modal-tambah-mhs')" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.master.mahasiswa.store') }}" style="padding: 1.25rem;">
            @csrf
            <div style="margin-bottom: 0.85rem;">
                <label class="form-label">Nama Lengkap Mahasiswa <span style="color: #dc2626;">*</span></label>
                <input type="text" name="name" required class="form-control" placeholder="Contoh: Muhammad Ilham">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div>
                    <label class="form-label">NIM <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="nomor_induk" required class="form-control" placeholder="Contoh: 221000000015">
                </div>
                <div>
                    <label class="form-label">Program Studi <span style="color: #dc2626;">*</span></label>
                    <select name="program_studi_id" required class="form-control">
                        <option value="">-- Pilih Prodi --</option>
                        @foreach ($daftarProdi as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->kode }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 0.85rem;">
                <label class="form-label">Alamat Email <span style="color: #dc2626;">*</span></label>
                <input type="email" name="email" required class="form-control" placeholder="ilham@student.upb.ac.id">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem;">
                <div>
                    <label class="form-label">No. WhatsApp / HP</label>
                    <input type="text" name="no_hp" class="form-control" placeholder="0812xxxxxxxx">
                </div>
                <div>
                    <label class="form-label">Password Akun</label>
                    <input type="text" name="password" class="form-control" placeholder="Default: password">
                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.2rem;">Kosongkan bila default 'password'</div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" onclick="closeModal('modal-tambah-mhs')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Mahasiswa</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: EDIT MAHASISWA                                    -->
<!-- ======================================================== -->
<div id="modal-edit-mhs" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div class="modal-dialog" style="background: #fff; border-radius: 0.75rem; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; margin: auto;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #1c2b20;">✏️ Edit Data Mahasiswa</h3>
            <button type="button" onclick="closeModal('modal-edit-mhs')" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form id="form-edit-mhs" method="POST" action="" style="padding: 1.25rem;">
            @csrf
            @method('PUT')
            <div style="margin-bottom: 0.85rem;">
                <label class="form-label">Nama Lengkap Mahasiswa <span style="color: #dc2626;">*</span></label>
                <input type="text" id="edit-name" name="name" required class="form-control">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div>
                    <label class="form-label">NIM <span style="color: #dc2626;">*</span></label>
                    <input type="text" id="edit-nim" name="nomor_induk" required class="form-control">
                </div>
                <div>
                    <label class="form-label">Program Studi <span style="color: #dc2626;">*</span></label>
                    <select id="edit-prodi" name="program_studi_id" required class="form-control">
                        <option value="">-- Pilih Prodi --</option>
                        @foreach ($daftarProdi as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->kode }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 0.85rem;">
                <label class="form-label">Alamat Email <span style="color: #dc2626;">*</span></label>
                <input type="email" id="edit-email" name="email" required class="form-control">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem;">
                <div>
                    <label class="form-label">No. WhatsApp / HP</label>
                    <input type="text" id="edit-hp" name="no_hp" class="form-control">
                </div>
                <div>
                    <label class="form-label">Ganti Password (Opsional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tetap">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" onclick="closeModal('modal-edit-mhs')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Perbarui Mahasiswa</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: BATCH IMPORT MAHASISWA MENGGUNAKAN FILE CSV       -->
<!-- ======================================================== -->
<div id="modal-import-csv" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div class="modal-dialog" style="background: #fff; border-radius: 0.75rem; width: 100%; max-width: 520px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; margin: auto;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #1c2b20;">📥 Batch Import Mahasiswa (.CSV)</h3>
            <button type="button" onclick="closeModal('modal-import-csv')" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.master.mahasiswa.import-csv') }}" enctype="multipart/form-data" style="padding: 1.25rem;">
            @csrf
            
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 0.85rem; margin-bottom: 1rem; font-size: 0.82rem; color: #166534;">
                <div style="font-weight: 700; margin-bottom: 0.35rem;">Petunjuk Format File CSV:</div>
                <ul style="margin: 0; padding-left: 1.25rem; line-height: 1.5;">
                    <li>Gunakan format baris header: <code>nama,nim,email,kode_prodi,no_hp,password</code></li>
                    <li>Kolom <code>kode_prodi</code> dapat diisi kode (misal: <strong>TI</strong> atau <strong>SI</strong>) atau nama lengkap prodi.</li>
                    <li>Jika kolom <code>password</code> dikosongkan, sistem akan otomatis mengeset ke <code>password</code>.</li>
                    <li>Pemisah (delimiter) otomatis mendeteksi koma (<code>,</code>) atau titik koma (<code>;</code>).</li>
                </ul>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label class="form-label">Pilih File CSV (.csv) <span style="color: #dc2626;">*</span></label>
                <input type="file" name="file_csv" required accept=".csv,text/csv,text/plain" class="form-control" style="padding: 0.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.4rem;">
                    <span style="font-size: 0.72rem; color: var(--text-muted);">Maksimal ukuran file: 5 MB</span>
                    <a href="{{ route('admin.master.mahasiswa.template-csv') }}" style="font-size: 0.75rem; color: var(--primary); font-weight: 600; text-decoration: underline;">
                        Unduh Contoh Template CSV
                    </a>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" onclick="closeModal('modal-import-csv')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm" style="background: #2f855a; border-color: #276749;">Mulai Proses Import</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    // Pasang handler klik backdrop untuk menutup modal
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    });

    function editMahasiswa(data) {
        document.getElementById('edit-name').value = data.name;
        document.getElementById('edit-nim').value = data.nomor_induk;
        document.getElementById('edit-email').value = data.email;
        document.getElementById('edit-prodi').value = data.program_studi_id || '';
        document.getElementById('edit-hp').value = data.no_hp || '';
        
        const form = document.getElementById('form-edit-mhs');
        form.action = "{{ url('admin/master/mahasiswa') }}/" + data.id;

        openModal('modal-edit-mhs');
    }
</script>

@endsection
