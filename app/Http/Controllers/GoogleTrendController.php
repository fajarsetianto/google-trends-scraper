<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Google\GTrends;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Excel;
use App\Models\Imports\DatasImport;

class GoogleTrendController extends Controller
{
    protected $options = [
        'hl'  => 'en-US',
        'tz'  => -60, # last hour
        'geo' => 'ID',
    ];

    protected $gt;

    public function __construct(){
        $this->gt = new GTrends($this->options);
    }

    public function find(Request $request){
        // $timeset = [
        //     'now 1-d' => '1 hari terakhir',
        //     'now 7-d' => '7 hari terakhir',
        //     'today 1-m' => '1 bulan terakhir',
        //     'today 3-m' => '3 minggu terakhir',
        //     'today 12-m' => '1 tahun terakhir',
        //     'today 5-y' => '5 tahun terakhir'
        // ];
        $request->validate([
            'k' => ['string', 'required'],
            // 'timeseries' => ['string','required','in:'.implode(',', array_keys($timeset))],
            'dataset' => ['mimes:csv,xlsx,xls'],
        ]);
        
        $results = collect();
        $results->corelations = collect(explode(',',$request->input('k')))->map(function($keyword){
            return ['keyword' => $keyword];
        });

        
        
        $import = new DatasImport;
        Excel::import($import, $request->file('dataset'));
        $max = $import->data->max('cases');

        $import->data = $import->data->map(function($item) use ($max){
            $item['cases'] = (100/$max)*$item['cases'];
            return $item;
        });        

        $results->dataSet = $import->data;

        foreach($results->corelations as $corelation){
            $keyword = trim($corelation['keyword']);
            $items =  collect($this->gt->explore($keyword, 0, 'all','',['TIMESERIES'], 1.5)['TIMESERIES']);
            
            $items = $items->map(function($item){
                        $item['date'] = Carbon::parse($item['formattedAxisTime']);
                        $item['value'] = $item['value'][0];
                        return $item;
            });
              
            $items = $items->filter(function($item) use ($import){
                return ($item['date'] >= $import->data->first()['date']) && ($item['date'] <= $import->data->last()['date']);
            });
            $maxi = $items->max('value');
            $items = $items->map(function($item) use ($maxi){
                $item['value'] = (100/$maxi)*$item['value'];
                return $item;
            });   
            $results->dataSet = $results->dataSet->map(function($data) use ($items, $keyword){
                $data[str_replace(' ', '', $keyword)] = $items->where('date', $data['date'])->first()['value'];
                return $data;
            }); 
        }
            
        $results->corelations = $results->corelations->map(function($corelation) use ($results){
            $keyword = $corelation['keyword'];

            $n = $results->dataSet->count();
            $sigmaX = $results->dataSet->sum('cases');
            $sigmaY = $results->dataSet->sum(str_replace(' ', '', $keyword));
            $sigmaXY = $results->dataSet->sum(function($item) use ($keyword){
                return $item['cases'] * $item[str_replace(' ', '', $keyword)];
            });
            $sigmaXsigmaY = $sigmaX * $sigmaY;

            $sigmaX2 = $results->dataSet->sum(function($item){
                return pow($item['cases'],2);
            });
            $sigmaX22 = pow($sigmaX,2);

            $sigmaY2 = $results->dataSet->sum(function($item) use ($keyword){
                return pow($item[str_replace(' ', '', $keyword)],2);
            });
            $sigmaY22 = pow($sigmaY,2);
            
            // $corelation['value'] = (($n * $sigmaXY) - $sigmaXsigmaY) / ((sqrt(($n*$sigmaX2) - $sigmaX22) * (sqrt(($n*$sigmaY2) - $sigmaY22))));
            (($n * $sigmaXY) - $sigmaXsigmaY) / ((sqrt(($n*$sigmaX2) - pow($sigmaX2,2)) * (sqrt(($n*$sigmaY2) - pow($sigmaY2,2)))));
            
            return $corelation;
        });
        // dd(implode(',',$results->corelations->pluck('keyword')->toArray()));
            
        return view('pages.search', compact('results'));
            
        return 'Tidak ada data';
        // }else{
        //     return redirect('/')->withErrors($validator);
        // }
        // return abort(404);
    }
}
