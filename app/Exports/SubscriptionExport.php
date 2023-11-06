<?php

namespace App\Exports;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubscriptionExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{

    protected $id;

    public function __construct($id) {
        $this->id = $id;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        if (is_null($this->id)) {
            $data = DB::table('subscriptions')
                ->join('users', 'users.id', '=', 'subscriptions.user_id')
                ->join('plans', 'plans.id', 'subscriptions.plan_id')
                ->select('subscriptions.user_id', 'users.name as user_name', 'subscriptions.parent_id', 'plans.name as plan_name', 'users.health_points', 'subscriptions.created_at')
                ->get();

            // $data = DB::table('point_distribution')
            // ->where('point_distribution.beneficiary_id', $this->id)
            // ->join('users', 'users.id', '=', 'point_distribution.user_id')
            // ->join('users as beneficiary', 'beneficiary.id', '=', 'point_distribution.beneficiary_id')
            // ->join('plans', 'plans.id', '=', 'point_distribution.plan_id')
            // ->select('users.name as user_name', 'users.phone_number as user_phone', 'users.parent_id as parent_id', 'beneficiary.name as beneficiary_name', 'beneficiary.phone_number as beneficiary_phone', 'plans.name', 'point_distribution.*')
            // ->get();
        } else {
            $data = DB::table('subscriptions')
            ->where('subscriptions.user_id', $this->id)
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->join('plans', 'plans.id', 'subscriptions.plan_id')
            ->select('subscriptions.user_id', 'users.name as user_name', 'subscriptions.parent_id', 'plans.name as plan_name', 'users.health_points', 'subscriptions.created_at')
            ->get();
        }

        return $data;
    }

    public function headings(): array
    {
        return ["User ID", "User name", "Senior ID", "Plan Name", "Health Points", "Timestamp"];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
