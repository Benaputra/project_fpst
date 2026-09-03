@extends('layouts.app')

@section('title', 'Master Data Program Studi - FPST UPB')
@section('page_title', 'Master Data Program Studi')

@section('content')

@include('admin.master._nav')

<!-- Stat Box -->
<div class="grid-stats" style="margin-bottom: 1.25rem;">
    <div class="stat-box">
        <div class="stat-box-title">Total Program Studi</div>
        <div class="stat-box-value">{{ $totalProdi }}</div>
    </div>
</div>

<div class="card" style="padding: 1.25rem;">
    <!-- Toolbar Aksi & Filter -->
    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
        <button type="button" onclick="openModal('modal-tambah-prodi')" class="btn btn-primary btn-sm" style="height: 38px; display: inline-flex; align-items: center; gap: 0.35rem;">
            <span>➕</span> Tambah Program Studi
        </button>

        <!-- Pencarian -->
        <form method="GET" action="{{ route('admin.master.prodi.index') }}" style="display: flex; gap: 0.5rem; align-items: center;">
            <div style="position: relative; width: 250px;">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari Nama / Kode Prodi..." class="form-control" style="font-size: 0.82rem; height: 38px; padding-left: 2rem;">
                <span style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: var(--text-muted);">🔍</span>
            </div>

            <button type="submit" class="btn btn-secondary btn-sm" style="height: 38px;">Cari</button>
            @if ($search)
                <a href="{{ route('admin.master.prodi.index') }}" class="btn btn-secondary btn-sm" style="height: 38px;" title="Reset Filter">✕</a>
            @endif
        </form>
    </div>

    <!-- Tabel Data Program Studi -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th style="width: 120px;">Kode Prodi</th>
                    <th>Nama Program Studi</th>
                    <th style="width: 130px; text-align: center;">Mahasiswa</th>
                    <th style="width: 130px; text-align: center;">Dosen</th>
                    <th style="width: 130px; text-align: center;">Admin Prodi</th>
                    <th style="width: 130px; text-align: center;">Total Skripsi</th>
                    <th style="width: 130px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarProdi as $index => $prodi)
                    <tr>
                        <td style="text-align: center; color: var(--text-muted); font-size: 0.82rem;">
                            {{ $daftarProdi->firstItem() + $index }}
                        </td>
                        <td>
                            <strong style="color: #1c2b20; font-family: monospace; font-size: 0.95rem; background: #f0fdf4; padding: 0.2rem 0.5rem; border-radius: 0.35rem; border: 1px solid #bbf7d0;">
                                {{ $prodi->kode ?: '-' }}
                            </strong>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #1c2b20; font-size: 0.95rem;">
                                {{ $prodi->nama }}
                            </div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">
                                Dibuat: {{ $prodi->created_at->format('d M Y') }}
                            </div>
                        </td>
                        <td style="text-align: center; font-weight: 600;">
                            {{ $prodi->mahasiswa_count }}
                        </td>
                        <td style="text-align: center; font-weight: 600;">
                            {{ $prodi->dosen_count }}
                        </td>
                        <td style="text-align: center; font-weight: 600;">
                            {{ $prodi->admin_count }}
                        </td>
                        <td style="text-align: center; font-weight: 600;">
                            {{ $prodi->pengajuan_skripsi_count }}
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 0.35rem; justify-content: center;">
                                <button type="button" 
                                    class="btn btn-secondary btn-sm" 
                                    style="padding: 0.25rem 0.5rem; font-size: 0.78rem;"
                                    title="Edit Prodi"
                                    onclick="editProdi({{ json_encode([
                                        'id' => $prodi->id,
                                        'nama' => $prodi->nama,
                                        'kode' => $prodi->kode,
                                    ]) }})">
                                    ✏️ Edit
                                </button>

                                <form method="POST" action="{{ route('admin.master.prodi.destroy', $prodi->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus program studi {{ $prodi->nama }}?')" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.78rem; background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;" title="Hapus Prodi">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            Tidak ada program studi ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.25rem;">
        {{ $daftarProdi->links() }}
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: TAMBAH PROGRAM STUDI                              -->
<!-- ======================================================== -->
<div id="modal-tambah-prodi" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div class="modal-dialog" style="background: #fff; border-radius: 0.75rem; width: 100%; max-width: 480px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; margin: auto;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #1c2b20;">➕ Tambah Program Studi</h3>
            <button type="button" onclick="closeModal('modal-tambah-prodi')" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.master.prodi.store') }}" style="padding: 1.25rem;">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label class="form-label">Nama Program Studi <span style="color: #dc2626;">*</span></label>
                <input type="text" name="nama" required class="form-control" placeholder="Contoh: Teknik Elektro">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label class="form-label">Kode Singkatan Prodi</label>
                <input type="text" name="kode" class="form-control" placeholder="Contoh: TE" style="text-transform: uppercase;">
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.25rem;">Maksimal 10 karakter. Digunakan untuk identifikasi CSV dan nomor surat.</div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" onclick="closeModal('modal-tambah-prodi')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Program Studi</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: EDIT PROGRAM STUDI                                -->
<!-- ======================================================== -->
<div id="modal-edit-prodi" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div class="modal-dialog" style="background: #fff; border-radius: 0.75rem; width: 100%; max-width: 480px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; margin: auto;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #1c2b20;">✏️ Edit Program Studi</h3>
            <button type="button" onclick="closeModal('modal-edit-prodi')" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form id="form-edit-prodi" method="POST" action="" style="padding: 1.25rem;">
            @csrf
            @method('PUT')
            <div style="margin-bottom: 1rem;">
                <label class="form-label">Nama Program Studi <span style="color: #dc2626;">*</span></label>
                <input type="text" id="edit-prodi-nama" name="nama" required class="form-control">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label class="form-label">Kode Singkatan Prodi</label>
                <input type="text" id="edit-prodi-kode" name="kode" class="form-control" style="text-transform: uppercase;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" onclick="closeModal('modal-edit-prodi')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Perbarui Program Studi</button>
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

    function editProdi(data) {
        document.getElementById('edit-prodi-nama').value = data.nama;
        document.getElementById('edit-prodi-kode').value = data.kode || '';
        
        const form = document.getElementById('form-edit-prodi');
        form.action = "{{ url('admin/master/prodi') }}/" + data.id;

        openModal('modal-edit-prodi');
    }
</script>

@endsection
