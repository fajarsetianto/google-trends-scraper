<?php

namespace App\Imports;

use App\Rules\Exceldate;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class DatasetImport implements WithColumnFormatting,ToCollection{

    private $data;

    // public function collection(Collection $rows)
    // {
    //     $rows = $rows->filter(function($row){
    //         return $row['A'] != null || $row['B'] != null; //remove empty row && prevent empty sheet
    //     });
        
    //     Validator::make(['data' => $rows->toArray()], [
    //         'data' => ['min: 1'],
    //         'data.*.A' => ['bail', 'required_with:data.*.B','nullable' ,
    //             function($attribute, $value, $fail){
    //                 try {
    //                     Date::excelToDateTimeObject($value);
    //                 } catch (\ErrorException $e) {
    //                     $fail($attribute.' is not valid excel date format.');
    //                 }
    //             },
    //             function($attribute, $value, $fail){
    //                 try {
    //                     Date::excelToDateTimeObject($value);
    //                 } catch (\ErrorException $e) {
    //                     $fail($attribute.' is not valid excel date format.');
    //                 }
    //             },
    //         ],
    //         'data.*.B' => ['required_with:data.*.A','nullable'],
    //     ])->validate();

    //     $this->data = $rows->transform(function($row){
    //         return [
    //             'date_object' => Carbon::instance(Date::excelToDateTimeObject($row['A'])),
    //             'date' =>  $row['A'],
    //             'value' => $row['B']  
    //         ];
    //     })->sortBy(function($row){
    //         return strtotime($row['date_object']);
    //     }, \SORT_REGULAR, true);
    // }


    public function collection(Collection $rows)
    {
        
        dd($rows);
        Validator::make(['data' => $rows->toArray()], [
            'data.*.start_date' => ['bail','required','numeric',new Exceldate],
            'data.*.end_date' => ['bail','required','numeric',new Exceldate],
            'data.*.value' => ['bail','required','numeric']],
            [
                'numeric' => 'The :attribute must be a valid excel date format'
            ]
            )->validate();

        $rows = $rows->transform(function($row){
            return [
                'start_date'  => Carbon::parse(Date::excelToDateTimeObject($row['start_date'])),
                'end_date' => Carbon::parse(Date::excelToDateTimeObject($row['end_date'])),
                'value' => $row['value'],
            ];
        });

        dd($rows);

        Validator::make(['data' => $rows->toArray()], [
            'data' => ['bail', 'min:1', function($attribute, $value, $fail) use ($rows){ 
                $gtErrors = '';
                $beetwenErrors = '';
                for($y = 0; $y < $rows->count(); $y++){
                    $curentStartDate = $rows[$y]['start_date'];
                    $curentEndDate = $rows[$y]['end_date'];
                    if($curentStartDate > $curentEndDate){
                        $gtErrors .= 'data.'.$y.'.start_date is greater than data.'.$y.'.end_date<br>';
                    }elseif($gtErrors == ''){
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
                    
                }
                if($gtErrors != ''){
                    $fail($gtErrors);
                    return;
                }
                if($beetwenErrors != ''){
                    $fail($beetwenErrors);
                }

            }]
        ])->validate();
        $this->data = $rows;
    }

    public function getData(){
        return $this->data;
    }

    // public function map($row): array
    // {
    //     return [
    //         'start_date'  => isset($row[0]) ? $row[0] : null,
    //         'end_date' => isset($row[1]) ? $row[1] : null,
    //         'value' => isset($row[2]) ? $row[2] : null,
    //     ];
    // }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'B' => NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE,
        ];
    }
   
}