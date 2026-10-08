<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class StudentImportTemplateExport implements FromArray
{
    public function array(): array
    {
        return [
            ['nama', 'NISN', 'tanggal_lahir', 'angkatan', 'kelas', 'nama_orang_tua'],
            ['Nama Siswa', '0012345678', '2012-05-17', 2026, '7A', 'Nama Orang Tua'],
        ];
    }
}
