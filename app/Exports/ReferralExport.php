<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReferralExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = DB::table('point_distribution')
            ->where(['point_distribution.purpose' => 'referrals'])
            ->join('users', 'users.id', '=', 'point_distribution.user_id')
            ->join('users as beneficiary', 'beneficiary.id', '=', 'point_distribution.beneficiary_id')
            ->join('plans', 'plans.id', '=', 'point_distribution.plan_id')
            ->select('point_distribution.user_id', 'users.name as user_name', 'users.phone_number as user_phone', 'point_distribution.beneficiary_id', 'beneficiary.name as beneficiary_name', 'beneficiary.phone_number as beneficiary_phone', 'plans.name as plan_name', 'point_distribution.points as health_points')
            ->get();

        return $data;
    }

    public function headings(): array
    {
        return ["User ID", "User Name", "User Phone", "Beneficiary ID", "Beneficiary Name", "Beneficiary Phone", "Plan", "Points"];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
