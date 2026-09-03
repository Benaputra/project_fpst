<div style="margin-bottom: 1.5rem;">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.4rem; font-weight: 800; color: #18241c; line-height: 1.2;">
                Master Data FPST
            </h1>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                Kelola data institusi, pengguna, dosen, mahasiswa, program studi, dan pemetaan penugasan admin.
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <span class="badge badge--danger" style="font-size: 0.75rem; padding: 0.4rem 0.85rem; font-weight: 700;">
                🛡️ Hak Akses: Admin Utama
            </span>
        </div>
    </div>

    <!-- Tab Bar Navigasi Master Data -->
    <div class="tab-nav" style="background: #fff; border: 1px solid var(--border); border-radius: 0.65rem; padding: 0.35rem 0.5rem; margin-bottom: 0;">
        <a href="{{ route('admin.master.mahasiswa.index') }}" class="tab-btn {{ request()->routeIs('admin.master.mahasiswa.*') ? 'active' : '' }}" style="text-decoration: none;">
            🎓 1. Data Mahasiswa
        </a>
        <a href="{{ route('admin.master.dosen.index') }}" class="tab-btn {{ request()->routeIs('admin.master.dosen.*') ? 'active' : '' }}" style="text-decoration: none;">
            👨‍🏫 2. Data Dosen & Kaprodi
        </a>
        <a href="{{ route('admin.master.user.index') }}" class="tab-btn {{ request()->routeIs('admin.master.user.*') ? 'active' : '' }}" style="text-decoration: none;">
            👤 3. Manajemen User & Role
        </a>
        <a href="{{ route('admin.master.prodi.index') }}" class="tab-btn {{ request()->routeIs('admin.master.prodi.*') ? 'active' : '' }}" style="text-decoration: none;">
            🏛️ 4. Program Studi
        </a>
        <a href="{{ route('admin.master.admin-prodi.index') }}" class="tab-btn {{ request()->routeIs('admin.master.admin-prodi.*') ? 'active' : '' }}" style="text-decoration: none;">
            🛡️ 5. Admin Program Studi
        </a>
    </div>
</div>
