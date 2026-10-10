<!-- ========================================== -->
<!-- MODAL INTERAKTIF PREVIEW DOKUMEN (FPST)    -->
<!-- ========================================== -->
<div id="modal-preview-dokumen" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.72); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 0.75rem;" role="dialog" aria-modal="true" aria-labelledby="modal-preview-title">
    <div style="background: #ffffff; border-radius: 0.85rem; width: 100%; max-width: 960px; height: 90vh; max-height: 860px; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35); border: 1px solid var(--border); overflow: hidden; animation: modalZoom 0.2s cubic-bezier(0.16, 1, 0.3, 1);">
        <!-- Header Modal -->
        <div style="padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--border); background: #f8fafc; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 0.6rem; min-width: 0;">
                <span style="font-size: 1.35rem; flex-shrink: 0;">📄</span>
                <div style="min-width: 0;">
                    <h3 id="modal-preview-title" style="font-size: 0.95rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0;">
                        Pratinjau Dokumen
                    </h3>
                    <div style="font-size: 0.72rem; color: #446850; font-weight: 600; margin-top: 0.1rem;">
                        Portal Skripsi FPST &bull; Universitas Panca Bhakti
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi Header -->
            <div style="display: flex; align-items: center; gap: 0.45rem; flex-shrink: 0;">
                <a id="modal-preview-tab-btn" href="#" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm" style="font-size: 0.75rem; padding: 0.3rem 0.65rem; height: 32px; min-height: 32px;" title="Buka Dokumen di Tab Baru Browser">
                    <span>↗</span> <span class="hide-modal-mobile">Tab Baru</span>
                </a>
                <a id="modal-preview-download-btn" href="#" class="btn btn-primary btn-sm" style="font-size: 0.75rem; padding: 0.3rem 0.65rem; height: 32px; min-height: 32px;" title="Unduh File PDF">
                    <span>📥</span> <span class="hide-modal-mobile">Unduh PDF</span>
                </a>
                <button type="button" onclick="closePreviewModal()" style="background: #e2e8f0; border: none; border-radius: 0.45rem; width: 32px; height: 32px; font-weight: 700; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; transition: background 0.15s;" title="Tutup Pratinjau (Esc)">
                    ✕
                </button>
            </div>
        </div>

        <!-- Banner Info Mobile Helper -->
        <div id="modal-preview-mobile-tip" style="display: none; background: #eff6ff; border-bottom: 1px solid #bfdbfe; padding: 0.45rem 1rem; font-size: 0.72rem; color: #1e40af; align-items: center; justify-content: space-between;">
            <span>💡 <em>Jika pratinjau tidak tampil di ponsel Anda, gunakan tombol <strong>Tab Baru</strong> atau <strong>Unduh PDF</strong>.</em></span>
        </div>

        <!-- Content Frame Container -->
        <div style="flex: 1; position: relative; background: #334155; overflow: hidden; display: flex; flex-direction: column;">
            <!-- Loading Indicator -->
            <div id="modal-preview-loading" style="position: absolute; inset: 0; background: #f8fafc; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.75rem; z-index: 10;">
                <div style="width: 36px; height: 36px; border: 3px solid #d1ded4; border-top-color: #446850; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                <div style="font-size: 0.82rem; font-weight: 600; color: #446850;">Memuat Dokumen...</div>
            </div>

            <!-- Iframe Dokumen -->
            <iframe id="modal-preview-iframe" src="" style="width: 100%; height: 100%; border: none; flex: 1; background: #ffffff;" onload="onPreviewIframeLoaded()"></iframe>
        </div>
    </div>
</div>

<style>
@keyframes modalZoom {
    from { opacity: 0; transform: scale(0.96); }
    to { opacity: 1; transform: scale(1); }
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
@media (max-width: 640px) {
    .hide-modal-mobile {
        display: none !important;
    }
}
</style>

<script>
    /**
     * Buka modal pratinjau dokumen PDF secara interaktif
     * @param {string} viewUrl - Route dokumen.view dengan base64 encoded path
     * @param {string} downloadUrl - Route dokumen.download dengan base64 encoded path
     * @param {string} title - Judul dokumen untuk header modal
     */
    function openPreviewModal(viewUrl, downloadUrl, title = 'Pratinjau Dokumen') {
        const modal = document.getElementById('modal-preview-dokumen');
        const iframe = document.getElementById('modal-preview-iframe');
        const titleEl = document.getElementById('modal-preview-title');
        const tabBtn = document.getElementById('modal-preview-tab-btn');
        const downloadBtn = document.getElementById('modal-preview-download-btn');
        const loading = document.getElementById('modal-preview-loading');
        const mobileTip = document.getElementById('modal-preview-mobile-tip');

        if (!modal) return;

        titleEl.innerText = title;
        tabBtn.href = viewUrl;
        downloadBtn.href = downloadUrl || viewUrl;

        // Tampilkan indikator loading dan buka modal
        loading.style.display = 'flex';
        iframe.src = 'about:blank';
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Tampilkan petunjuk jika layar sempit / mobile
        if (window.innerWidth <= 768 && mobileTip) {
            mobileTip.style.display = 'flex';
        } else if (mobileTip) {
            mobileTip.style.display = 'none';
        }

        // Delay sekejap untuk stabilitas DOM iframe
        setTimeout(() => {
            iframe.src = viewUrl;
        }, 50);
    }

    function onPreviewIframeLoaded() {
        const iframe = document.getElementById('modal-preview-iframe');
        const loading = document.getElementById('modal-preview-loading');
        if (iframe && iframe.src && iframe.src !== 'about:blank' && loading) {
            loading.style.display = 'none';
        }
    }

    function closePreviewModal() {
        const modal = document.getElementById('modal-preview-dokumen');
        const iframe = document.getElementById('modal-preview-iframe');
        if (!modal) return;

        modal.style.display = 'none';
        if (iframe) iframe.src = 'about:blank';
        document.body.style.overflow = '';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('modal-preview-dokumen');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closePreviewModal();
                }
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.getElementById('modal-preview-dokumen');
                if (modal && modal.style.display === 'flex') {
                    closePreviewModal();
                }
            }
        });
    });
</script>
