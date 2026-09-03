@extends('layouts.app')

@section('title', 'Master Data Dosen - FPST UPB')
@section('page_title', 'Master Data Dosen & Kaprodi')

@section('content')

@include('admin.master._nav')

<!-- Stat Box -->
<div class="grid-stats" style="margin-bottom: 1.25rem;">
    <div class="stat-box">
        <div class="stat-box-title">Total Dosen Terdaftar</div>
        <div class="stat-box-value">{{ $totalDosen }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Dosen Pengajar</div>
        <div class="stat-box-value">{{ $totalDosen - $totalKaprodi }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Ketua Program Studi (Kaprodi)</div>
        <div class="stat-box-value">{{ $totalKaprodi }}</div>
    </div>
</div>

<div class="card" style="padding: 1.25rem;">
    <!-- Toolbar Aksi & Filter -->
    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
        <button type="button" onclick="openModal('modal-tambah-dosen')" class="btn btn-primary btn-sm" style="height: 38px; display: inline-flex; align-items: center; gap: 0.35rem;">
            <span>➕</span> Tambah Dosen
        </button>

        <!-- Filter & Pencarian -->
        <form method="GET" action="{{ route('admin.master.dosen.index') }}" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
            <select name="role" class="form-control" style="font-size: 0.82rem; height: 38px; width: 150px;" onchange="this.form.submit()">
                <option value="">Semua Peran</option>
                <option value="dosen" {{ $roleFilter === 'dosen' ? 'selected' : '' }}>Dosen Pengajar</option>
                <option value="kaprodi" {{ $roleFilter === 'kaprodi' ? 'selected' : '' }}>Kaprodi</option>
            </select>

            <select name="prodi_id" class="form-control" style="font-size: 0.82rem; height: 38px; width: 170px;" onchange="this.form.submit()">
                <option value="">Semua Prodi</option>
                @foreach ($daftarProdi as $prodi)
                    <option value="{{ $prodi->id }}" {{ $prodiFilter == $prodi->id ? 'selected' : '' }}>
                        {{ $prodi->kode ?: $prodi->nama }}
                    </option>
                @endforeach
            </select>

            <div style="position: relative; width: 220px;">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari Nama / NIDN / Email..." class="form-control" style="font-size: 0.82rem; height: 38px; padding-left: 2rem;">
                <span style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: var(--text-muted);">🔍</span>
            </div>

            <button type="submit" class="btn btn-secondary btn-sm" style="height: 38px;">Cari</button>
            @if ($search || $prodiFilter || $roleFilter)
                <a href="{{ route('admin.master.dosen.index') }}" class="btn btn-secondary btn-sm" style="height: 38px;" title="Reset Filter">✕</a>
            @endif
        </form>
    </div>

    <!-- Tabel Data Dosen -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th style="width: 140px;">NIDN / NIP</th>
                    <th>Nama Dosen & Gelar</th>
                    <th style="width: 160px;">Program Studi</th>
                    <th style="width: 130px;">Peran</th>
                    <th>Kontak</th>
                    <th style="width: 170px;">Beban Skripsi</th>
                    <th style="width: 130px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarDosen as $index => $dosen)
                    <tr>
                        <td style="text-align: center; color: var(--text-muted); font-size: 0.82rem;">
                            {{ $daftarDosen->firstItem() + $index }}
                        </td>
                        <td>
                            <strong style="color: #1c2b20; font-family: monospace; font-size: 0.9rem;">
                                {{ $dosen->nomor_induk }}
                            </strong>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #1c2b20;">{{ $dosen->name }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $dosen->email }}</div>
                        </td>
                        <td>
                            @if ($dosen->programStudi)
                                <span class="badge badge--primary" style="font-size: 0.75rem;">
                                    {{ $dosen->programStudi->nama }} ({{ $dosen->programStudi->kode }})
                                </span>
                            @else
                                <span class="badge badge--secondary" style="font-size: 0.75rem;">Belum diset</span>
                            @endif
                        </td>
                        <td>
                            @if ($dosen->isKaprodi())
                                <span class="badge badge--purple" style="font-size: 0.75rem; font-weight: 700;">
                                    🏛️ Kaprodi
                                </span>
                            @else
                                <span class="badge badge--primary" style="font-size: 0.75rem;">
                                    👨‍🏫 Dosen
                                </span>
                            @endif
                        </td>
                        <td style="font-size: 0.82rem;">
                            @if ($dosen->no_hp)
                                <div>📱 {{ $dosen->no_hp }}</div>
                            @else
                                <span style="color: var(--text-muted); font-style: italic;">-</span>
                            @endif
                        </td>
                        <td style="font-size: 0.78rem;">
                            <div>📚 Pembimbing: <strong>{{ $dosen->bimbingan_pertama_count + $dosen->bimbingan_kedua_count }}</strong> mhs</div>
                            <div style="color: var(--text-muted);">⚖️ Penguji: {{ $dosen->menguji_seminar_count + $dosen->menguji_sidang_pertama_count + $dosen->menguji_sidang_kedua_count }} kali</div>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 0.35rem; justify-content: center;">
                                <button type="button" 
                                    class="btn btn-secondary btn-sm" 
                                    style="padding: 0.25rem 0.5rem; font-size: 0.78rem;"
                                    title="Edit Dosen"
                                    onclick="editDosen({{ json_encode([
                                        'id' => $dosen->id,
                                        'name' => $dosen->name,
                                        'nomor_induk' => $dosen->nomor_induk,
                                        'email' => $dosen->email,
                                        'program_studi_id' => $dosen->program_studi_id,
                                        'role' => $dosen->role->value,
                                        'no_hp' => $dosen->no_hp,
                                    ]) }})">
                                    ✏️ Edit
                                </button>

                                <form method="POST" action="{{ route('admin.master.dosen.destroy', $dosen->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data dosen {{ $dosen->name }}?')" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.78rem; background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;" title="Hapus Dosen">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            Tidak ada data dosen ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.25rem;">
        {{ $daftarDosen->links() }}
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: TAMBAH DOSEN                                      -->
<!-- ======================================================== -->
<div id="modal-tambah-dosen" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div class="modal-dialog" style="background: #fff; border-radius: 0.75rem; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; margin: auto;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #1c2b20;">➕ Tambah Data Dosen</h3>
            <button type="button" onclick="closeModal('modal-tambah-dosen')" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.master.dosen.store') }}" style="padding: 1.25rem;">
            @csrf
            <div style="margin-bottom: 0.85rem;">
                <label class="form-label">Nama Dosen & Gelar <span style="color: #dc2626;">*</span></label>
                <input type="text" name="name" required class="form-control" placeholder="Contoh: Dr. Budi Santoso, M.Kom.">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div>
                    <label class="form-label">NIDN / NIP <span style="color: #dc2626;">*</span></label>
                    <input type="text" name="nomor_induk" required class="form-control" placeholder="Contoh: 1000000020">
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

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div>
                    <label class="form-label">Peran / Jabatan <span style="color: #dc2626;">*</span></label>
                    <select name="role" required class="form-control">
                        <option value="dosen">Dosen Pengajar</option>
                        <option value="kaprodi">Ketua Prodi (Kaprodi)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">No. WhatsApp / HP</label>
                    <input type="text" name="no_hp" class="form-control" placeholder="0812xxxxxxxx">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem;">
                <div>
                    <label class="form-label">Alamat Email <span style="color: #dc2626;">*</span></label>
                    <input type="email" name="email" required class="form-control" placeholder="dosen@upb.ac.id">
                </div>
                <div>
                    <label class="form-label">Password Akun</label>
                    <input type="text" name="password" class="form-control" placeholder="Default: password">
                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.2rem;">Kosongkan jika default</div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" onclick="closeModal('modal-tambah-dosen')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Dosen</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: EDIT DOSEN                                        -->
<!-- ======================================================== -->
<div id="modal-edit-dosen" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div class="modal-dialog" style="background: #fff; border-radius: 0.75rem; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; margin: auto;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #1c2b20;">✏️ Edit Data Dosen</h3>
            <button type="button" onclick="closeModal('modal-edit-dosen')" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form id="form-edit-dosen" method="POST" action="" style="padding: 1.25rem;">
            @csrf
            @method('PUT')
            <div style="margin-bottom: 0.85rem;">
                <label class="form-label">Nama Dosen & Gelar <span style="color: #dc2626;">*</span></label>
                <input type="text" id="edit-dosen-name" name="name" required class="form-control">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div>
                    <label class="form-label">NIDN / NIP <span style="color: #dc2626;">*</span></label>
                    <input type="text" id="edit-dosen-nidn" name="nomor_induk" required class="form-control">
                </div>
                <div>
                    <label class="form-label">Program Studi <span style="color: #dc2626;">*</span></label>
                    <select id="edit-dosen-prodi" name="program_studi_id" required class="form-control">
                        <option value="">-- Pilih Prodi --</option>
                        @foreach ($daftarProdi as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->kode }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div>
                    <label class="form-label">Peran / Jabatan <span style="color: #dc2626;">*</span></label>
                    <select id="edit-dosen-role" name="role" required class="form-control">
                        <option value="dosen">Dosen Pengajar</option>
                        <option value="kaprodi">Ketua Prodi (Kaprodi)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">No. WhatsApp / HP</label>
                    <input type="text" id="edit-dosen-hp" name="no_hp" class="form-control">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem;">
                <div>
                    <label class="form-label">Alamat Email <span style="color: #dc2626;">*</span></label>
                    <input type="email" id="edit-dosen-email" name="email" required class="form-control">
                </div>
                <div>
                    <label class="form-label">Ganti Password (Opsional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tetap">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" onclick="closeModal('modal-edit-dosen')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Perbarui Dosen</button>
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

    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    });

    function editDosen(data) {
        document.getElementById('edit-dosen-name').value = data.name;
        document.getElementById('edit-dosen-nidn').value = data.nomor_induk;
        document.getElementById('edit-dosen-email').value = data.email;
        document.getElementById('edit-dosen-prodi').value = data.program_studi_id || '';
        document.getElementById('edit-dosen-role').value = data.role;
        document.getElementById('edit-dosen-hp').value = data.no_hp || '';
        
        const form = document.getElementById('form-edit-dosen');
        form.action = "{{ url('admin/master/dosen') }}/" + data.id;

        openModal('modal-edit-dosen');
    }
</script>

@endsection
