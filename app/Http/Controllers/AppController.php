<?php

namespace App\Http\Controllers;

use App\Imports\DatasetImport;
use App\Imports\DatasetImports;
use App\Jobs\FetchGoogleTrend;
use App\Models\Queue;
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

        $first_date = $dataSet->first()['start_date'];
        $last_date = $dataSet->last()['end_date'];

        $periods = collect(CarbonPeriod::create($first_date,'8 months', $last_date));        
        
        $formatedPeriods = $periods->map(function($value){
            return [
                'start_date' => $value->copy(),
                'end_date' => $value->copy()->addMonths(8)->subDays(1)
            ];
        });

        $queue = Queue::create([
            'dataset' => $dataSet->toArray(),
            'keywords' => $input['keyword'],
            'category' => $input['kategori'],
            'is_finished' => 0,
        ]);
        FetchGoogleTrend::dispatch($queue);
        return view('search', compact('queue'));
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

    public function debug(){
        $g = new GoogleTrend([
            'hl'  => 'id',
            'tz'  => -60, # last hour
            'geo' => 'ID',
        ]);
        // dd('a');
        $data = $g->finterestBySubregion(['corona'], 'WEEK' ,0 , '2019-06-08 2020-06-08');
        dd($data);
    }

    public function jobs(Queue $queue){
        // logger('info', $queue->toArray());
        if($queue->is_finished){
            return response()->json([
                'is_finished' => $queue->is_finished,
                'dataset' => $queue->dataset
            ],200);
        }
        return response()->json([
            'is_finished' => $queue->is_finished,
        ],200);
    }

   

}