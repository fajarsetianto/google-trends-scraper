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
        $categories = collect($this->gTrends->getCategories()['children'])->prepend(['name'=> 'Semua Kategori','id'=>0]);
        return view('pages.new', compact('categories'));
    }

    

    public function search(Queue $queue = null, Request $request, DatasetImports $import){
        // dd($request->all());

        $categories = $this->flatten($this->gTrends->getCategories());
        $input = $request->all();
        $input['keyword'] = $input['keyword'] == null ? [] : explode(',', $input['keyword']); 
        $request->replace($input);
        $request->validate([
            'kategori' => 'required|in:'.implode(',',$categories),
            'keyword' => ['required','array','min:1','max:10'],
            'dataset' => ['required_without:use_old'],
            'use_old' => ['required_without:dataset']
        ]);

        $currentCategory = $queue != null ? $queue->category : null;
        
        if($queue!= null && $request->input('use_old') != null){
            $queue->update([
                'keywords' => $input['keyword'],
                'fetched_keywords' => $currentCategory != $input['kategori'] ? [] : $queue->fetched_keywords,
                'category' => $input['kategori'],
            ]);
            if(collect($input['keyword'])->diff(collect($queue->fetched_keywords))->isEmpty()){
                return redirect()->route('results', [$queue->id]);
            }
            $queue->update([
                'status' => 1
            ]);

        }else{
            Excel::import($import, $request->file('dataset'));
            if($import->data == null){
                return back()->withErrors(['dataset' => 'dataset should not be empty'])->withInput($input);
            }
            $dataSet = $import->data->sortBy(function($value){
                return $value['start_date'];
            })->values();
            $max = $dataSet->max('value');
            $dataSet = $dataSet->map(function($data) use ($max) {
                $data['value'] = (100/$max) * $data['value'];
                return $data;
            });
            if($queue){
                $queue->update([
                    'data' => $dataSet,
                    'keywords' => $input['keyword'],
                    'fetched_keywords' => [],
                    'category' => $input['kategori'],
                    'status' => 1
                ]);
            }else{
                $queue = Queue::create([
                    'data' => $dataSet,
                    'keywords' => $input['keyword'],
                    'fetched_keywords' => [],
                    'category' => $input['kategori'],
                ]);
            }
        }
        FetchGoogleTrend::dispatch($queue);
        return redirect()->route('progress', [$queue->id]);
    }

    public function progress(Queue $queue){
        // return view('pages.progress', compact('queue'));
        switch($queue->status){
            case 0:
                break;
            case 4:
                return redirect()->route('results', [$queue->id]);
                break;
            default:
                return view('pages.progress', compact('queue'));
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
                $categories = collect($this->gTrends->getCategories()['children'])->prepend(['name'=> 'Semua Kategori','id'=>0]);
                return view('pages.results', compact('queue','categories'));
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