<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayoutExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection($role = 'agent')
    {
        $data = DB::table('users')
            ->join('commission_requests', 'commission_requests.user_id', '=', 'users.id', 'left')
            ->join('user_parent', 'user_parent.user_id', 'users.id')
            ->join('users as parent', 'parent.id', 'user_parent.parent_id')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', $role)
            ->where(function ($q) {
                $q->where('status', 'approved')
                    ->orWhere('status', null);
            })
            ->select('users.name', 'users.id as users_id', 'users.created_at as register_at', 'users.wallet', 'users.email', 'users.phone_number', 'users.active', 'commission_requests.id', 'commission_requests.user_id', 'commission_requests.request_amount', 'commission_requests.status', 'roles.name as role', 'parent.name as parent_name', 'parent.id as parent_id')
            // ->groupBy('users.name', 'commission_requests.id', 'commission_requests.user_id', 'commission_requests.request_amount', 'commission_requests.status', 'users_id')
            ->get()
            ->groupBy('users_id')->map(function ($q) {
                return ['user_id' => $q[0]->users_id, 'name' => $q[0]->name, 'wallet' => $q[0]->wallet, 'payout' => $q->sum('request_amount'), 'role' => $q[0]->role, 'email' => $q[0]->email, 'phone_number' => $q[0]->phone_number, 'active' => $q[0]->active, 'parent_id' => $q[0]->parent_id, 'parent_name' => $q[0]->parent_name, 'created_at' => $q[0]->register_at];
            });

        return $data;
    }

    public function headings(): array
    {
        return ["User ID", "Name", "Commission Earned", "Payout", "Role", "Email", "Phone", "Active", "Parent ID", "Parent Name", "Register At"];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
