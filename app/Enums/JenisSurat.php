<?php

namespace App\Enums;

enum JenisSurat: string
{
    case KesediaanPembimbing = 'kesediaan_pembimbing';
    case SkBimbingan = 'sk_bimbingan';
    case UndanganSeminar = 'undangan_seminar';
    case SuratTugasSeminar = 'surat_tugas_seminar';
    case UndanganSidang = 'undangan_sidang';
    case SuratTugasSidang = 'surat_tugas_sidang';
}
