<?php

namespace App\Exports;

use App\Models\Mype;
use Maatwebsite\Excel\Concerns\FromCollection;

class MypesExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Mype::all();
    }
}
