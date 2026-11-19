<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class StudentImportTemplateExport implements FromArray
{
    public function array(): array
    {
        return [
            ['nama', 'angkatan', 'nisn', 'tanggal_lahir'],
            ['Nama Siswa', 2026, '0012345678', '2012-05-17'],
        ];
    }
}
