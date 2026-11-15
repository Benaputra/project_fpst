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
        <div class="stat-box-title">Tendik & Pimpinan</div>
        <div class="stat-box-value">{{ $roleCounts['tendik'] + $roleCounts['pimpinan_fakultas'] }}</div>
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
            <select name="role" class="form-control" style="font-size: 0.82rem; height: 38px; width: 190px;" onchange="this.form.submit()">
                <option value="">Semua Role</option>
                @foreach (\App\Enums\UserRole::cases() as $roleCase)
                    <option value="{{ $roleCase->value }}" {{ $roleFilter === $roleCase->value ? 'selected' : '' }}>{{ $roleCase->label() }}</option>
                @endforeach
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
                            @if ($u->jabatan || $u->unit_kerja)
                                <div style="font-size: 0.74rem; color: var(--text-muted);">
                                    {{ $u->jabatan ?: '' }}{{ $u->jabatan && $u->unit_kerja ? ' • ' : '' }}{{ $u->unit_kerja ?: '' }}
                                </div>
                            @endif
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
                            <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                <span class="badge {{ $u->role->badgeClass() }}" style="font-size: 0.75rem; font-weight: 700;">
                                    {{ $u->role->label() }}
                                </span>
                                @foreach ($u->extraRoles as $extra)
                                    @if ($extra->role)
                                        <span class="badge {{ $extra->role->badgeClass() }}" style="font-size: 0.68rem; opacity: 0.88;" title="Role Tambahan">
                                            + {{ $extra->role->label() }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
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
                                        'extra_roles' => $u->extraRoles->map(fn ($r) => $r->role?->value)->filter()->values()->all(),
                                        'program_studi_id' => $u->program_studi_id,
                                        'no_hp' => $u->no_hp,
                                        'jabatan' => $u->jabatan,
                                        'unit_kerja' => $u->unit_kerja,
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

<script>
    function editUser(data) {
        document.getElementById('edit-user-name').value = data.name;
        document.getElementById('edit-user-email').value = data.email;
        document.getElementById('edit-user-nidn').value = data.nomor_induk || '';
        document.getElementById('edit-user-role').value = data.role;
        document.getElementById('edit-user-prodi').value = data.program_studi_id || '';
        document.getElementById('edit-user-hp').value = data.no_hp || '';
        document.getElementById('edit-user-jabatan').value = data.jabatan || '';
        document.getElementById('edit-user-unit-kerja').value = data.unit_kerja || '';

        const extraRoles = Array.isArray(data.extra_roles) ? data.extra_roles : [];
        document.querySelectorAll('.edit-extra-role-checkbox').forEach((cb) => {
            cb.checked = extraRoles.includes(cb.value);
        });
        
        const warning = document.getElementById('self-warning');
        const roleSelect = document.getElementById('edit-user-role');
        const roleHidden = document.getElementById('edit-user-role-self');
        if (data.is_self) {
            warning.style.display = 'block';
            roleSelect.disabled = true;
            if (roleHidden) {
                roleHidden.disabled = false;
                roleHidden.value = data.role;
            }
        } else {
            warning.style.display = 'none';
            roleSelect.disabled = false;
            if (roleHidden) {
                roleHidden.disabled = true;
            }
        }

        const form = document.getElementById('form-edit-user');
        form.action = "{{ url('admin/master/user') }}/" + data.id;

        openModal('modal-edit-user');
    }
</script>

@endsection

@push('modals')
<!-- ======================================================== -->
<!-- MODAL: TAMBAH USER BARU                                  -->
<!-- ======================================================== -->
<div id="modal-tambah-user" class="modal-backdrop" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-dialog-header">
            <h3 class="modal-dialog-title"><span>➕</span> Tambah Pengguna Baru</h3>
            <button type="button" onclick="closeModal('modal-tambah-user')" class="modal-dialog-close">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.master.user.store') }}">
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
                        @foreach (\App\Enums\UserRole::cases() as $roleCase)
                            <option value="{{ $roleCase->value }}">{{ $roleCase->label() }}</option>
                        @endforeach
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

            <div style="margin-bottom: 0.85rem;">
                <label class="form-label">Role Tambahan / Rangkap Jabatan (Opsional)</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem 0.9rem; font-size: 0.8rem; background: #f8faf8; padding: 0.65rem 0.75rem; border: 1px solid var(--border); border-radius: 0.5rem;">
                    @foreach (\App\Enums\UserRole::cases() as $roleCase)
                        <label style="display: inline-flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                            <input type="checkbox" name="extra_roles[]" value="{{ $roleCase->value }}">
                            <span>{{ $roleCase->label() }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div>
                    <label class="form-label">Jabatan Struktural</label>
                    <input type="text" name="jabatan" class="form-control" placeholder="Misal: Dekan / Staf TU">
                </div>
                <div>
                    <label class="form-label">Unit Kerja</label>
                    <input type="text" name="unit_kerja" class="form-control" placeholder="Misal: Subbag Akademik FPST">
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

            <div class="modal-dialog-footer">
                <button type="button" onclick="closeModal('modal-tambah-user')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: EDIT USER & GANTI ROLE                            -->
<!-- ======================================================== -->
<div id="modal-edit-user" class="modal-backdrop" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-dialog-header">
            <h3 class="modal-dialog-title"><span>🔄</span> Edit Pengguna & Pergantian Role</h3>
            <button type="button" onclick="closeModal('modal-edit-user')" class="modal-dialog-close">&times;</button>
        </div>
        <form id="form-edit-user" method="POST" action="">
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
                    @foreach (\App\Enums\UserRole::cases() as $roleCase)
                        <option value="{{ $roleCase->value }}">{{ $roleCase->label() }}</option>
                    @endforeach
                </select>
                <input type="hidden" id="edit-user-role-self" name="role" value="" disabled>
                <div id="self-warning" style="display: none; font-size: 0.72rem; color: #dc2626; margin-top: 0.35rem;">
                    * Ini adalah akun Anda yang sedang aktif. Anda tidak dapat menurunkan role akun sendiri.
                </div>

                <div style="margin-top: 0.7rem;">
                    <div style="font-size: 0.78rem; color: #166534; font-weight: 600; margin-bottom: 0.35rem;">Role Tambahan (Rangkap Peran):</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.45rem 0.85rem; font-size: 0.78rem;">
                        @foreach (\App\Enums\UserRole::cases() as $roleCase)
                            <label style="display: inline-flex; align-items: center; gap: 0.3rem; cursor: pointer;">
                                <input type="checkbox" name="extra_roles[]" value="{{ $roleCase->value }}" class="edit-extra-role-checkbox">
                                <span>{{ $roleCase->label() }}</span>
                            </label>
                        @endforeach
                    </div>
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

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                <div>
                    <label class="form-label">Jabatan Struktural</label>
                    <input type="text" id="edit-user-jabatan" name="jabatan" class="form-control" placeholder="Misal: Dekan / Staf TU">
                </div>
                <div>
                    <label class="form-label">Unit Kerja</label>
                    <input type="text" id="edit-user-unit-kerja" name="unit_kerja" class="form-control" placeholder="Misal: Subbag Akademik FPST">
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label class="form-label">Reset Password (Opsional)</label>
                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
            </div>

            <div class="modal-dialog-footer">
                <button type="button" onclick="closeModal('modal-edit-user')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Perbarui User & Role</button>
            </div>
        </form>
    </div>
</div>
@endpush
