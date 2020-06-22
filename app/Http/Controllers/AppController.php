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
        $categories = collect($this->gTrends->getCategories()['children'])->prepend(['name'=> 'semua kategori','id'=>0]);
        // dd($categories);
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
            'dataset' => $dataSet->values(),
            'keywords' => $input['keyword'],
            'category' => $input['kategori'],
        ]);
        FetchGoogleTrend::dispatch($queue);

        return redirect()->route('progress', [$queue->id]);
    }

    public function progress(Queue $queue){
        switch($queue->status){
            case 0:
                break;
            case 4:
                return redirect()->route('results', [$queue->id]);
                break;
            default:
                return view('progress', compact('queue'));
                break;
        }
    }

    public function results(Queue $queue){
        switch($queue->status){
            case 0:
                break;
            case 4:
                // $corelation = collect();
                // $dataset = collect($queue->dataset);
                // $realCases = $dataset->pluck('value');
                // $n = $realCases->count();
                // $sigmaX = $realCases->sum();
                // $sigmaX2 = $realCases->sum(function($value){
                //     return pow($value,2);
                // });

                // foreach($queue->keywords as $keyword){
                //     $currentTrendData = $dataset->pluck('keywords.'.$keyword);
                    
                //     $sigmaY = $currentTrendData->sum();
                //     $sigmaY2 = $currentTrendData->sum(function($value){
                //         return pow($value,2);
                //     });
                //     dd($currentTrendData->zip($realCases)->map(function($item){return '('.$item[0].','.$item[1].')';})->implode(' '));
                //     $sigmaXY = $currentTrendData->zip($realCases)->sum(function($item){
                //         return $item[0] * $item[1];
                //     });
                //     $top = ($sigmaXY - (($sigmaX * $sigmaY) / $n));
                //     $bottom = sqrt(($sigmaX2 - (pow($sigmaX,2) / $n)) * ($sigmaY2 - (pow($sigmaY,2) / $n)));
                    
                //     $corelation[$keyword] = $top / ($bottom != 0 ? $bottom : 1);
                //     // $corelation[$keyword] = ($sigmaXY - (($sigmaX * $sigmaY) / $n)) / sqrt(($sigmaX2 - (pow($sigmaX,2) / $n)) * ($sigmaY2 - (pow($sigmaY,2) / $n)));
                // }
                // dd($queue->corelation);
                return view('results', compact('queue'));
                break;
            default:
                return redirect()->route('progress',[$queue->id]);
                break;
        }
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
        switch($queue->status){
            case 1:
                return response()->json([
                 'status' => $queue->status,
                 'jobs_a_head' => Queue::whereNotIn('status',[0,4])->where('created_at','<', $queue->created_at)->count()
                ],200);
                break;
            default:
                return response()->json($queue->only(['status']),200);
                break;
        }
    }

   

}