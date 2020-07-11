<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class DatasetImports implements ToCollection,WithChunkReading, WithCustomValueBinder, WithMapping
{

    public $data;
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        // dd(['dataset' => $rows->toArray()]);
        Validator::make(['dataset' => $rows->toArray()], [
            'dataset' => ['min:1'],
            'dataset.*.start_date' => ['required','date'],
            'dataset.*.end_date' => ['bail','required','date', 'after_or_equal:dataset.*.start_date'],
            'dataset.*.value' => ['required','numeric']]
        )->validate();

        $rows = $rows->transform(function($row){
            return [
                'start_date'  => Carbon::parse($row['start_date']),
                'end_date' => Carbon::parse($row['end_date']),
                'value' => $row['value'],
            ];
        });
        // dd($rows);
        Validator::make(['data' => $rows->toArray()], [
            'data' => ['bail', 'min:1', function($attribute, $value, $fail) use ($rows){ 
                $beetwenErrors = '';
                for($y = 0; $y < $rows->count()-1; $y++){
                    $curentStartDate = $rows[$y]['start_date'];
                    $curentEndDate = $rows[$y]['end_date'];
                    for($i = $y + 1; $i < $rows->count(); $i++){
                        $startDate = $rows[$i]['start_date'];
                        $endDate =$rows[$i]['end_date'];
        
                        if($curentStartDate->between($startDate, $endDate)){
                            $beetwenErrors .= 'data.'.$y.'.start_date is between data.'.$i.'<br>';
                        }
                        if($startDate->between($curentStartDate, $curentEndDate)){
                            $beetwenErrors .= 'data.'.$i.'.start_date is between data.'.$y.'<br>';
                        }
                        if($curentEndDate->between($startDate, $endDate)){
                            $beetwenErrors .= 'data.'.$y.'.end_date is between data.'.$i.'<br>';
                        }
                        if($endDate->between($curentStartDate, $curentEndDate)){
                            $beetwenErrors .= 'data.'.$i.'.end_date is between data.'.$y.'<br>';
                        }
                    }
                }
                
                if($beetwenErrors != ''){
                    $fail($beetwenErrors);
                }

            }]
        ])->validate();

        $this->data = $rows;
        
    }

    public function bindValue(Cell $cell, $value)
    {
        if(preg_match('/^(A|B)*\d*$/', $cell->getCoordinate())){
                 $cell->setValueExplicit(Date::excelToDateTimeObject($value)->format('Y-m-d'), DataType::TYPE_STRING);
         }
         else{
             $cell->setValueExplicit($value, DataType::TYPE_STRING);
         }
 
         return true;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function map($row): array
    {
        return [
            'start_date'  => $row[0],
            'end_date' => $row[1],
            'value' => $row[2],
        ];
    }


}
