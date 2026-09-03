@extends('layouts.app')

@section('title', 'Master Data Admin Prodi - FPST UPB')
@section('page_title', 'Master Data Admin Program Studi')

@section('content')

@include('admin.master._nav')

<!-- Stat Box -->
<div class="grid-stats" style="margin-bottom: 1.25rem;">
    <div class="stat-box">
        <div class="stat-box-title">Total Admin Program Studi</div>
        <div class="stat-box-value">{{ $totalAdminProdi }}</div>
    </div>
    @foreach ($daftarProdi as $p)
        <div class="stat-box">
            <div class="stat-box-title">Admin {{ $p->kode ?: $p->nama }}</div>
            <div class="stat-box-value">{{ $p->users()->where('role', 'admin_prodi')->count() }}</div>
        </div>
    @endforeach
</div>

<div class="card" style="padding: 1.25rem;">
    <!-- Toolbar Aksi & Filter -->
    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
        <button type="button" onclick="openModal('modal-tambah-admin-prodi')" class="btn btn-primary btn-sm" style="height: 38px; display: inline-flex; align-items: center; gap: 0.35rem;">
            <span>➕</span> Tambah Admin Prodi
        </button>

        <!-- Filter & Pencarian -->
        <form method="GET" action="{{ route('admin.master.admin-prodi.index') }}" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
            <select name="prodi_id" class="form-control" style="font-size: 0.82rem; height: 38px; width: 180px;" onchange="this.form.submit()">
                <option value="">Semua Program Studi</option>
                @foreach ($daftarProdi as $prodi)
                    <option value="{{ $prodi->id }}" {{ $prodiFilter == $prodi->id ? 'selected' : '' }}>
                        {{ $prodi->kode ?: $prodi->nama }}
                    </option>
                @endforeach
            </select>

            <div style="position: relative; width: 230px;">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari Nama / Email / NIP..." class="form-control" style="font-size: 0.82rem; height: 38px; padding-left: 2rem;">
                <span style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: var(--text-muted);">🔍</span>
            </div>

            <button type="submit" class="btn btn-secondary btn-sm" style="height: 38px;">Cari</button>
            @if ($search || $prodiFilter)
                <a href="{{ route('admin.master.admin-prodi.index') }}" class="btn btn-secondary btn-sm" style="height: 38px;" title="Reset Filter">✕</a>
            @endif
        </form>
    </div>

    <!-- Tabel Data Admin Prodi -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th>Nama Admin Prodi</th>
                    <th style="width: 150px;">NIP / Nomor Induk</th>
                    <th>Email</th>
                    <th style="width: 200px;">Program Studi Dikelola</th>
                    <th>Kontak</th>
                    <th style="width: 130px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarAdminProdi as $index => $admin)
                    <tr>
                        <td style="text-align: center; color: var(--text-muted); font-size: 0.82rem;">
                            {{ $daftarAdminProdi->firstItem() + $index }}
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #1c2b20;">{{ $admin->name }}</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Terdaftar: {{ $admin->created_at->format('d M Y') }}</div>
                        </td>
                        <td>
                            <strong style="color: #1c2b20; font-family: monospace; font-size: 0.88rem;">
                                {{ $admin->nomor_induk ?: '-' }}
                            </strong>
                        </td>
                        <td style="font-size: 0.85rem;">{{ $admin->email }}</td>
                        <td>
                            @if ($admin->programStudi)
                                <span class="badge badge--warning" style="font-size: 0.78rem; font-weight: 700;">
                                    🏛️ {{ $admin->programStudi->nama }} ({{ $admin->programStudi->kode }})
                                </span>
                            @else
                                <span class="badge badge--danger" style="font-size: 0.75rem;">Belum dipetakan</span>
                            @endif
                        </td>
                        <td style="font-size: 0.82rem;">
                            @if ($admin->no_hp)
                                <div>📱 {{ $admin->no_hp }}</div>
                            @else
                                <span style="color: var(--text-muted); font-style: italic;">-</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 0.35rem; justify-content: center;">
                                <button type="button" 
                                    class="btn btn-secondary btn-sm" 
                                    style="padding: 0.25rem 0.5rem; font-size: 0.78rem;"
                                    title="Edit Admin Prodi"
                                    onclick="editAdminProdi({{ json_encode([
                                        'id' => $admin->id,
                                        'name' => $admin->name,
                                        'email' => $admin->email,
                                        'nomor_induk' => $admin->nomor_induk,
                                        'program_studi_id' => $admin->program_studi_id,
                                        'no_hp' => $admin->no_hp,
                                    ]) }})">
                                    ✏️ Edit
                                </button>

                                <form method="POST" action="{{ route('admin.master.admin-prodi.destroy', $admin->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun Admin Prodi {{ $admin->name }}?')" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.78rem; background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;" title="Hapus Admin Prodi">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            Tidak ada data Admin Prodi ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.25rem;">
        {{ $daftarAdminProdi->links() }}
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: TAMBAH ADMIN PRODI                                -->
<!-- ======================================================== -->
<div id="modal-tambah-admin-prodi" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div class="modal-dialog" style="background: #fff; border-radius: 0.75rem; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; margin: auto;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #1c2b20;">➕ Tambah Admin Program Studi</h3>
            <button type="button" onclick="closeModal('modal-tambah-admin-prodi')" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.master.admin-prodi.store') }}" style="padding: 1.25rem;">
            @csrf
            <div style="margin-bottom: 0.85rem;">
                <label class="form-label">Nama Lengkap Admin <span style="color: #dc2626;">*</span></label>
                <input type="text" name="name" required class="form-control" placeholder="Contoh: Admin Akademik TI">
            </div>

            <div style="margin-bottom: 0.85rem;">
                <label class="form-label">Program Studi yang Dikelola <span style="color: #dc2626;">*</span></label>
                <select name="program_studi_id" required class="form-control" style="font-weight: 600;">
                    <option value="">-- Pilih Program Studi --</option>
                    @foreach ($daftarProdi as $p)
                        <option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->kode }})</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div>
                    <label class="form-label">Alamat Email <span style="color: #dc2626;">*</span></label>
                    <input type="email" name="email" required class="form-control" placeholder="admin.prodi@upb.ac.id">
                </div>
                <div>
                    <label class="form-label">NIP / Nomor Induk</label>
                    <input type="text" name="nomor_induk" class="form-control" placeholder="ADM-001">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem;">
                <div>
                    <label class="form-label">No. WhatsApp / HP</label>
                    <input type="text" name="no_hp" class="form-control" placeholder="0812xxxxxxxx">
                </div>
                <div>
                    <label class="form-label">Password Akun</label>
                    <input type="text" name="password" class="form-control" placeholder="Default: password">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" onclick="closeModal('modal-tambah-admin-prodi')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Admin Prodi</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: EDIT ADMIN PRODI                                  -->
<!-- ======================================================== -->
<div id="modal-edit-admin-prodi" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div class="modal-dialog" style="background: #fff; border-radius: 0.75rem; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; margin: auto;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #1c2b20;">✏️ Edit Data Admin Prodi</h3>
            <button type="button" onclick="closeModal('modal-edit-admin-prodi')" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form id="form-edit-admin-prodi" method="POST" action="" style="padding: 1.25rem;">
            @csrf
            @method('PUT')
            
            <div style="margin-bottom: 0.85rem;">
                <label class="form-label">Nama Lengkap Admin <span style="color: #dc2626;">*</span></label>
                <input type="text" id="edit-admin-name" name="name" required class="form-control">
            </div>

            <div style="margin-bottom: 0.85rem;">
                <label class="form-label">Program Studi yang Dikelola <span style="color: #dc2626;">*</span></label>
                <select id="edit-admin-prodi-id" name="program_studi_id" required class="form-control" style="font-weight: 600;">
                    <option value="">-- Pilih Program Studi --</option>
                    @foreach ($daftarProdi as $p)
                        <option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->kode }})</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div>
                    <label class="form-label">Alamat Email <span style="color: #dc2626;">*</span></label>
                    <input type="email" id="edit-admin-email" name="email" required class="form-control">
                </div>
                <div>
                    <label class="form-label">NIP / Nomor Induk</label>
                    <input type="text" id="edit-admin-nidn" name="nomor_induk" class="form-control">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem;">
                <div>
                    <label class="form-label">No. WhatsApp / HP</label>
                    <input type="text" id="edit-admin-hp" name="no_hp" class="form-control">
                </div>
                <div>
                    <label class="form-label">Ganti Password (Opsional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Kosongkan bila tetap">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" onclick="closeModal('modal-edit-admin-prodi')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Perbarui Admin Prodi</button>
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

    function editAdminProdi(data) {
        document.getElementById('edit-admin-name').value = data.name;
        document.getElementById('edit-admin-email').value = data.email;
        document.getElementById('edit-admin-nidn').value = data.nomor_induk || '';
        document.getElementById('edit-admin-prodi-id').value = data.program_studi_id || '';
        document.getElementById('edit-admin-hp').value = data.no_hp || '';
        
        const form = document.getElementById('form-edit-admin-prodi');
        form.action = "{{ url('admin/master/admin-prodi') }}/" + data.id;

        openModal('modal-edit-admin-prodi');
    }
</script>

@endsection
