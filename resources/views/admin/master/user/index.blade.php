@extends('layouts.app')

@section('title', 'Manajemen User & Role - FPST UPB')
@section('page_title', 'Manajemen User & Pergantian Role')

@section('content')

@include('admin.master._nav')

<!-- Stat Box Counters per Role -->
<div class="grid-stats" style="margin-bottom: 1.25rem;">
    <div class="stat-box">
        <div class="stat-box-title">Total Pengguna</div>
        <div class="stat-box-value">{{ $totalUser }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Mahasiswa</div>
        <div class="stat-box-value">{{ $roleCounts['mahasiswa'] }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Dosen & Kaprodi</div>
        <div class="stat-box-value">{{ $roleCounts['dosen'] + $roleCounts['kaprodi'] }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Admin Prodi</div>
        <div class="stat-box-value">{{ $roleCounts['admin_prodi'] }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Admin Utama</div>
        <div class="stat-box-value">{{ $roleCounts['admin_utama'] }}</div>
    </div>
</div>

<div class="card" style="padding: 1.25rem;">
    <!-- Toolbar Aksi & Filter -->
    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
        <button type="button" onclick="openModal('modal-tambah-user')" class="btn btn-primary btn-sm" style="height: 38px; display: inline-flex; align-items: center; gap: 0.35rem;">
            <span>➕</span> Tambah User Baru
        </button>

        <!-- Filter & Pencarian -->
        <form method="GET" action="{{ route('admin.master.user.index') }}" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
            <select name="role" class="form-control" style="font-size: 0.82rem; height: 38px; width: 170px;" onchange="this.form.submit()">
                <option value="">Semua Role</option>
                <option value="mahasiswa" {{ $roleFilter === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                <option value="dosen" {{ $roleFilter === 'dosen' ? 'selected' : '' }}>Dosen</option>
                <option value="kaprodi" {{ $roleFilter === 'kaprodi' ? 'selected' : '' }}>Kaprodi</option>
                <option value="admin_prodi" {{ $roleFilter === 'admin_prodi' ? 'selected' : '' }}>Admin Prodi</option>
                <option value="admin_utama" {{ $roleFilter === 'admin_utama' ? 'selected' : '' }}>Admin Utama</option>
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
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari Nama / Email / No Induk..." class="form-control" style="font-size: 0.82rem; height: 38px; padding-left: 2rem;">
                <span style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: var(--text-muted);">🔍</span>
            </div>

            <button type="submit" class="btn btn-secondary btn-sm" style="height: 38px;">Cari</button>
            @if ($search || $roleFilter || $prodiFilter)
                <a href="{{ route('admin.master.user.index') }}" class="btn btn-secondary btn-sm" style="height: 38px;" title="Reset Filter">✕</a>
            @endif
        </form>
    </div>

    <!-- Tabel Data User -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th>Nama Pengguna</th>
                    <th style="width: 150px;">Nomor Induk</th>
                    <th>Email</th>
                    <th style="width: 140px;">Role Saat Ini</th>
                    <th style="width: 160px;">Program Studi</th>
                    <th style="width: 140px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarUser as $index => $u)
                    <tr>
                        <td style="text-align: center; color: var(--text-muted); font-size: 0.82rem;">
                            {{ $daftarUser->firstItem() + $index }}
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #1c2b20;">
                                {{ $u->name }}
                                @if ($u->id === auth()->id())
                                    <span class="badge badge--success" style="font-size: 0.68rem; margin-left: 0.25rem;">(Anda)</span>
                                @endif
                            </div>
                            @if ($u->no_hp)
                                <div style="font-size: 0.75rem; color: var(--text-muted);">📱 {{ $u->no_hp }}</div>
                            @endif
                        </td>
                        <td>
                            <strong style="color: #1c2b20; font-family: monospace; font-size: 0.88rem;">
                                {{ $u->nomor_induk ?: '-' }}
                            </strong>
                        </td>
                        <td style="font-size: 0.85rem;">{{ $u->email }}</td>
                        <td>
                            <span class="badge {{ $u->role->badgeClass() }}" style="font-size: 0.75rem; font-weight: 700;">
                                {{ $u->role->label() }}
                            </span>
                        </td>
                        <td>
                            @if ($u->programStudi)
                                <span class="badge badge--primary" style="font-size: 0.75rem;">
                                    {{ $u->programStudi->nama }} ({{ $u->programStudi->kode }})
                                </span>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.78rem;">-</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 0.35rem; justify-content: center;">
                                <button type="button" 
                                    class="btn btn-secondary btn-sm" 
                                    style="padding: 0.25rem 0.5rem; font-size: 0.78rem;"
                                    title="Edit Profil & Ganti Role"
                                    onclick="editUser({{ json_encode([
                                        'id' => $u->id,
                                        'name' => $u->name,
                                        'email' => $u->email,
                                        'nomor_induk' => $u->nomor_induk,
                                        'role' => $u->role->value,
                                        'program_studi_id' => $u->program_studi_id,
                                        'no_hp' => $u->no_hp,
                                        'is_self' => $u->id === auth()->id(),
                                    ]) }})">
                                    🔄 Role / Edit
                                </button>

                                @if ($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.master.user.destroy', $u->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun user {{ $u->name }}?')" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.78rem; background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;" title="Hapus User">
                                            🗑️
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            Tidak ada pengguna ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.25rem;">
        {{ $daftarUser->links() }}
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: TAMBAH USER BARU                                  -->
<!-- ======================================================== -->
<div id="modal-tambah-user" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div class="modal-dialog" style="background: #fff; border-radius: 0.75rem; width: 100%; max-width: 520px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; margin: auto;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #1c2b20;">➕ Tambah Pengguna Baru</h3>
            <button type="button" onclick="closeModal('modal-tambah-user')" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.master.user.store') }}" style="padding: 1.25rem;">
            @csrf
            <div style="margin-bottom: 0.85rem;">
                <label class="form-label">Nama Lengkap <span style="color: #dc2626;">*</span></label>
                <input type="text" name="name" required class="form-control" placeholder="Nama lengkap atau gelar">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div>
                    <label class="form-label">Email Pengguna <span style="color: #dc2626;">*</span></label>
                    <input type="email" name="email" required class="form-control" placeholder="user@upb.ac.id">
                </div>
                <div>
                    <label class="form-label">Nomor Induk (NIM/NIDN/NIP)</label>
                    <input type="text" name="nomor_induk" class="form-control" placeholder="Opsional jika staf">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div>
                    <label class="form-label">Role Pengguna <span style="color: #dc2626;">*</span></label>
                    <select name="role" required class="form-control">
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="dosen">Dosen</option>
                        <option value="kaprodi">Kaprodi</option>
                        <option value="admin_prodi">Admin Prodi</option>
                        <option value="admin_utama">Admin Utama</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Program Studi</label>
                    <select name="program_studi_id" class="form-control">
                        <option value="">-- Tanpa / Lintas Prodi --</option>
                        @foreach ($daftarProdi as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->kode }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem;">
                <div>
                    <label class="form-label">No. WhatsApp / HP</label>
                    <input type="text" name="no_hp" class="form-control" placeholder="0812xxxxxxxx">
                </div>
                <div>
                    <label class="form-label">Password</label>
                    <input type="text" name="password" class="form-control" placeholder="Default: password">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" onclick="closeModal('modal-tambah-user')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: EDIT USER & GANTI ROLE                            -->
<!-- ======================================================== -->
<div id="modal-edit-user" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div class="modal-dialog" style="background: #fff; border-radius: 0.75rem; width: 100%; max-width: 520px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; margin: auto;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #1c2b20;">🔄 Edit Pengguna & Pergantian Role</h3>
            <button type="button" onclick="closeModal('modal-edit-user')" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form id="form-edit-user" method="POST" action="" style="padding: 1.25rem;">
            @csrf
            @method('PUT')
            
            <div style="margin-bottom: 0.85rem;">
                <label class="form-label">Nama Lengkap <span style="color: #dc2626;">*</span></label>
                <input type="text" id="edit-user-name" name="name" required class="form-control">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div>
                    <label class="form-label">Email Pengguna <span style="color: #dc2626;">*</span></label>
                    <input type="email" id="edit-user-email" name="email" required class="form-control">
                </div>
                <div>
                    <label class="form-label">Nomor Induk (NIM/NIDN/NIP)</label>
                    <input type="text" id="edit-user-nidn" name="nomor_induk" class="form-control">
                </div>
            </div>

            <!-- GANTI ROLE DROPDOWN -->
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 0.85rem; margin-bottom: 0.85rem;">
                <label class="form-label" style="color: #166534; font-weight: 700;">
                    🛡️ Tetapkan / Ganti Role User:
                </label>
                <select id="edit-user-role" name="role" required class="form-control" style="font-weight: 600;">
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="dosen">Dosen</option>
                    <option value="kaprodi">Kaprodi</option>
                    <option value="admin_prodi">Admin Prodi</option>
                    <option value="admin_utama">Admin Utama</option>
                </select>
                <div id="self-warning" style="display: none; font-size: 0.72rem; color: #dc2626; margin-top: 0.35rem;">
                    * Ini adalah akun Anda yang sedang aktif. Anda tidak dapat menurunkan role akun sendiri.
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div>
                    <label class="form-label">Program Studi</label>
                    <select id="edit-user-prodi" name="program_studi_id" class="form-control">
                        <option value="">-- Tanpa / Lintas Prodi --</option>
                        @foreach ($daftarProdi as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->kode }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">No. WhatsApp / HP</label>
                    <input type="text" id="edit-user-hp" name="no_hp" class="form-control">
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label class="form-label">Reset Password (Opsional)</label>
                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" onclick="closeModal('modal-edit-user')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Perbarui User & Role</button>
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

    function editUser(data) {
        document.getElementById('edit-user-name').value = data.name;
        document.getElementById('edit-user-email').value = data.email;
        document.getElementById('edit-user-nidn').value = data.nomor_induk || '';
        document.getElementById('edit-user-role').value = data.role;
        document.getElementById('edit-user-prodi').value = data.program_studi_id || '';
        document.getElementById('edit-user-hp').value = data.no_hp || '';
        
        const warning = document.getElementById('self-warning');
        if (data.is_self) {
            warning.style.display = 'block';
            document.getElementById('edit-user-role').disabled = true;
        } else {
            warning.style.display = 'none';
            document.getElementById('edit-user-role').disabled = false;
        }

        const form = document.getElementById('form-edit-user');
        form.action = "{{ url('admin/master/user') }}/" + data.id;

        openModal('modal-edit-user');
    }
</script>

@endsection
