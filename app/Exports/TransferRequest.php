<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransferRequest implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $status;

    public function __construct($status)
    {
        $this->status = $status;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        if ($this->status == 'all') {
            $data =  DB::table('transfer_requests')
                ->join('users', 'users.id', '=', 'transfer_requests.user_id')
                ->join('users as receivers', 'receivers.id', '=', 'transfer_requests.receiver_id')
                ->where('transfer_requests.status', '!=', 'pending')
                ->where('transfer_requests.entity', 'points')
                ->select('transfer_requests.transaction_id', 'users.name as user_name', 'transfer_requests.value', 'receivers.name as receiver_name', 'transfer_requests.created_at', 'transfer_requests.status')
                ->get();
            return $data;
        } else {
            $data = DB::table('transfer_requests')
                ->join('users', 'users.id', '=', 'transfer_requests.user_id')
                ->join('users as receivers', 'receivers.id', '=', 'transfer_requests.receiver_id')
                ->where('transfer_requests.status', $this->status)
                ->where('transfer_requests.entity', 'points')
                ->select('transfer_requests.transaction_id', 'users.name as user_name', 'transfer_requests.value', 'receivers.name as receiver_name', 'transfer_requests.created_at', 'transfer_requests.status')
                ->get();
            return $data;
        }
    }

    public function headings(): array
    {
        return ["Trxn ID", "User name", "Points", "Beneficiary", "Timestamp", "Status"];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
