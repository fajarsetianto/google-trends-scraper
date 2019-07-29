<?php
namespace App\Models\Imports;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\ToCollection;

class DatasImport implements ToCollection
{
    public $data;

    public function __construct()
    {
        $this->data = new Collection();
    }
    public function collection(Collection $rows)
    {   
        foreach($rows as $row){
            $this->data->push(
                [
                    'date' => Carbon::parse(Date::excelToDateTimeObject($row[0])),
                    'formatedDate' => Carbon::parse(Date::excelToDateTimeObject($row[0]))->format('M Y'),
                    'cases' => $row[1]
                ]
            );
        }
        
    }
}