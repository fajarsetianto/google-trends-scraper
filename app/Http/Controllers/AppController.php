<?php

namespace App\Http\Controllers;

use App\Imports\DatasetImport;
use App\Imports\DatasetImports;
use App\Services\GoogleTrend;
use Google\GTrends;
use Illuminate\Http\Request;
use Carbon\CarbonPeriod;

use DatePeriod;
use Excel;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;


use PhpOffice\PhpSpreadsheet\Reader\IReader;

class AppController extends Controller{

    private $gTrends;

    public function __construct()
    {
        $this->gTrends = new GoogleTrend([
            'hl'  => 'id',
            'tz'  => -60, # last hour
            'geo' => 'ID',
        ]);
    }
    public function index(){
        $categories = $this->gTrends->getCategories()['children'];
        return view('home', compact('categories'));
        
    }

    public function search(Request $request, DatasetImports $import){
        $categories = $this->flatten($this->gTrends->getCategories());    
        $input = $request->all();
        $input['keyword'] = $input['keyword'] == null ? null: explode(',', $input['keyword']); 
        $request->replace($input);

        $request->validate([
            'kategori' => 'required|in:'.implode(',',$categories),
            'keyword' => ['required','min:1'],
            'dataset' => ['required']
        ]);

        Excel::import($import, $request->file('dataset'));

        $dataSet = $import->data->sortBy(function($value){
            return $value['start_date'];
        });

        //calculation for minimal iteration fetching data

        $first_date = $dataSet->first()['start_date'];
        $last_date = $dataSet->last()['end_date'];

        $periods = CarbonPeriod::create($first_date,'8 months', $last_date);

        $formatedPeriods = array_map(function($value){
            return [
                'start_date' => $value->copy(),
                'end_date' => $value->copy()->addMonths(8)->subDays(1)
            ];
        },$periods->toArray());

        $dataSet = $dataSet->mapToGroups(function($value) use ($formatedPeriods){
            for ($i=0; $i < sizeof($formatedPeriods); $i++) { 
                $periodStartDate = $formatedPeriods[$i]['start_date']->copy();
                $periodEndDate = $formatedPeriods[$i]['end_date']->copy();
                if($value['start_date']->between($periodStartDate, $periodEndDate) && $value['end_date']->between($periodStartDate, $periodEndDate)){
                    return  [$i => $value];
                }
            }
        });
        // return 'a';
        
        // dd($this->gTrends->explore($input['keyword'], $input['kategori'], 'all'));
        
        
        return view('search', compact('input', 'dataSet', 'formatedPeriods'));
    }

    public function getSuggestion(Request $request){
        return $this->gTrends->suggestionsAutocomplete($request->input('keyword'));
    }

    protected function flatten(Array $array){
        $newArr = [];
        array_push($newArr, $array['id']);
        if(isset($array['children'])){
            foreach($array['children'] as $ar ){
                foreach($this->flatten($ar) as $id){
                    array_push($newArr, $id);
                }
            }
        }
        return $newArr;
    }

    public function fetch(Request $request){
        $input = $request->all();
        $data = $this->gTrends->explore($input['keywords'], $input['category'], $input['start_date'].' '.$input['end_date']);
        return response()->json($data,200);
    }

    public function debug(){
        $g = new GoogleTrend([
            'hl'  => 'id',
            'tz'  => -60, # last hour
            'geo' => 'ID',
        ]);
        $data = $g->explore(['corona'], 0 , 'all','',['*'], 0.5);
        dd($data);
    }

   

}