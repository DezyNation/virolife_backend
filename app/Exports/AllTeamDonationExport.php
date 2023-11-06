<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AllTeamDonationExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{

    protected $purpose;
    protected $id;

    public function __construct($id, $purpose)
    {
        $this->id = $id;
        $this->purpose = $purpose;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {

        $data = DB::table('virolife_donation')
            ->where('purpose', 'all-team')
            ->join('users', 'users.id', '=', 'virolife_donation.user_id')
            ->select('user_id', 'users.name', 'users.stars', 'users.created_at', DB::raw('SUM(amount) as amount'), DB::raw('stars/((DATEDIFF(CURDATE() ,users.created_at)*0.032855)) as performance'))
            ->groupBy('user_id', 'users.name', 'users.stars', 'users.created_at')
            ->get();

        return $data;

    }

    public function headings(): array
    {
        return ["User ID", "User name", "User Stars", "Timestamp", "Amount", "Performance"];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
    
    // public function columnWidths(): array
    // {
    //     return [
    //         'A' => 10,
    //         'B' => 25,
    //         'C' => 10,
    //         'D' => 10,
    //         'E' => 10
    //     ];
    // }
}
