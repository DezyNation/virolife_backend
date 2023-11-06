<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LoginExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $data = DB::table('logins')
        ->join('users', 'users.id', '=', 'logins.user_id')
        ->whereBetween('logins.created_at', [Carbon::yesterday()->subDay(), Carbon::today()])
        ->select('logins.user_id', 'users.name', 'users.phone_number', 'logins.ip', 'logins.created_at')
        ->latest('logins.created_at')
        ->get();

        return $data;
    }

    public function headings(): array
    {
        return ["User ID", "User name", "User Phone", "IP", "Timestamp"];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}