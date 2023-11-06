<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class UserExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $role;

    public function __construct($role)
    {
        $this->role = $role;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $users = DB::table('users')
        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', '=', $this->role)
        ->join('donations', 'donations.donated_to', '=', 'users.id', 'left')
        ->join('point_distribution', 'point_distribution.beneficiary_id', '=', 'users.id', 'left')
        ->select('users.id', 'users.name', 'users.email', 'users.phone_number', 'users.dob', 'users.stars', 'users.ad_points', 'users.created_at', DB::raw('stars/((DATEDIFF(CURDATE() ,users.created_at)*0.032855)) as performance'),
        )
        ->groupBy('users.id', 'users.name', 'users.email', 'users.phone_number', 'users.dob', 'users.stars', 'users.ad_points', 'users.created_at')
        ->get();
        
        $points =  DB::table('users')
        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', '=', $this->role)
        ->leftJoin('point_distribution', function($join) {
            $join->on('point_distribution.beneficiary_id', '=', 'users.id');
            $join->where('expiry_at', '>', Carbon::now());
        })
        ->select('users.id', DB::raw('sum(point_distribution.points) as points'))
        ->groupBy('users.id')
        ->get();
        
        $primary_sum =  DB::table('users')
        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', '=', $role)
        ->leftJoin('donations', function($join) {
            $join->on('donations.donated_to', '=', 'users.id');
            $join->where('donations.group', '=', 'primary');
        })
        ->select('users.id', DB::raw('sum(amount) as primary_sum'))
        ->groupBy('users.id')
        ->get();
        
        $secondary_sum =  DB::table('users')
        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', '=', $this->role)
        ->leftJoin('donations', function($join) {
            $join->on('donations.donated_to', '=', 'users.id');
            $join->where('donations.group', '=', 'secondary');
        })
        ->select('users.id', DB::raw('sum(amount) as secondary_sum'))
        ->groupBy('users.id')
        ->get();
        
    $result = $users
    ->concat($points)
    ->concat($primary_sum)
    ->concat($secondary_sum)
    ->groupBy('id')
    ->map(function ($items) {
        return $items->reduce(function ($merged, $item) {
            return array_merge($merged, (array) $item);
        }, []);
    })
    ->values();
    
    return $result;
    }

    public function headings(): array
    {
        return ["ID", "Name", "Contact", "Email", "Phone Number", "Date of Birth", "Stars", "AD Points", "Registered On", "Health Points", "Primary Donation", "Secondary Donation"];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
