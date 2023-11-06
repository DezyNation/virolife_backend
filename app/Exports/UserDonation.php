<?php

namespace App\Exports;

use App\Models\Donation;
use Maatwebsite\Excel\Concerns\FromCollection;

class UserDonation implements FromCollection
{

    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        if (!is_null($this->id)) {
            $donations = Donation::with('user')->where(['donatable_id' => $this->id, 'donatable_type' => 'App\Models\User'])->get();
        } else {

            $donations = Donation::with(['user' => function($q){
                $q->select('id', 'name');
            }])->where(['donatable_type' => 'App\Models\User'])->get();
        }
        return $donations;
    }
}
