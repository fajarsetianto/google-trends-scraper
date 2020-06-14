<?php

namespace App\Jobs;

use App\Models\Queue;
use App\Services\GoogleTrend;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;


class FetchGoogleTrend
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $currentQueue;

    public $timeout = 1000;

    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Queue $queue)
    {
        $this->currentQueue = $queue;
    }

    /** 
     * Execute the job.
     *
     * @return void
     */
    public function handle(GoogleTrend $googleTrend)
    {
        /*
            Tidak di group karena, ada kemungkinan terdapat range data yang pada kedua periode
        */
        $googleTrend->setOptions([
            'hl'  => 'id',
            'tz'  => -60, # last hour
            'geo' => 'ID',
        ]);

        $dataset = collect($this->currentQueue->dataset)->map(function($data){
            $data['start_date'] = Carbon::parse($data['start_date']);
            $data['end_date'] = Carbon::parse($data['end_date']);
            return $data;
        });

        $keywords = collect($this->currentQueue->keywords)->chunk(5)->map(function($data){
            return $data->values();
        });
        $periods = $this->getPeriods($dataset);
        foreach($keywords as $keyword){
            $monthly = $googleTrend->explore(
                $keyword->toArray(),
                $this->currentQueue->category,
                'all'
            );
            if(is_array($monthly)){
                $monthly = collect($monthly['TIMESERIES']);
                if($monthly->isNotEmpty()){
                    $monthly = $monthly->mapWithKeys(function($data){
                        return [Carbon::createFromTimestamp($data['time'])->format('Y-m') => $data];
                    });
                    foreach($periods as $period){
                        $daily = $googleTrend->explore(
                            $keyword->toArray(),
                            $this->currentQueue->category,
                            $period['start_date']->format('Y-m-d').' '.$period['end_date']->format('Y-m-d')
                        );
                        if(is_array($daily)){
                            $daily = collect($daily['TIMESERIES']);
                            if($daily->isNotEmpty()){
                                //group daily data according to its month and year
                                $daily = $daily->groupBy(function($data){
                                    return Carbon::createFromTimestamp($data['time'])->format('Y-m');
                                });
                                // normalize daily data against monthly data;
                                $formatedDaily = [];
                                //lopping troguh daily group as month
                                foreach($daily as $month){
                                    //lopping trough keyword
                                    foreach($keyword as $key => $term){
                                        //sum all daily record for current keyword
                                        $totalDailyKeyword = $month->sum(function($daily) use ($key){
                                            return $daily['value'][$key];
                                        });
                                        //lopping trough grouped daily
                                        foreach($month as $day){
                                            $currentDay = Carbon::createFromTimestamp($day['time']);
                                            if($totalDailyKeyword == 0 || $monthly[$currentDay->format('Y-m')]['value'][$key] == 0){
                                                $formatedDaily[$currentDay->format('Y-m-d')]['value'][$key] = 0;
                                            }else{
                                                $formatedDaily[$currentDay->format('Y-m-d')]['value'][$key] = ($monthly[$currentDay->format('Y-m')]['value'][$key] / $totalDailyKeyword) * $day['value'][$key];
                                            }
                                        }
                                    }
                                }
                                //need to be find the best algorithm for saving this varaible bellow
                                $dataset = $dataset->map(function($data) use ($formatedDaily, $keyword){
                                    $currentStartDate = $data['start_date'];
                                    $currentEndDate = $data['end_date'];
                                    //find daily data that in range data
                                    $currentData = collect($formatedDaily)->filter(function($data ,$key) use ($currentStartDate, $currentEndDate){
                                        return Carbon::parse($key)->between($currentStartDate, $currentEndDate);
                                    });
                                    if($currentData->isNotEmpty()){
                                        foreach($keyword as $key=>$term){
                                            $totalCurent = $currentData->sum(function($data) use ($key){
                                                return $data['value'][$key];
                                            });
                                            $data['keywords'][$term] = isset($data['keywords'][$term]) ? $data['keywords'][$term] + $totalCurent: $totalCurent; 
                                            
                                        }
                                    }else{
                                        //current data empty
                                    }
                                    return $data;
                                });
                            }else{
                                //daily timeseries empty
                                logger('daily data empty');
                                //need to be find the best algorithm for saving this varaible bellow
                                $dataset = $dataset->map(function($data) use ($period, $keyword){
                                    if(!isset($data['keywords'][$keyword[0]])){
                                        if($data['start_date']->between($period['start_date'],$period['end_date']) ||
                                            $data['end_date']->between($period['start_date'],$period['end_date'])
                                        ){
                                            foreach($keyword as $value){
                                                $data['keywords'][$value] = 0;
                                            }
                                        }
                                    }
                                    return $data;
                                });
                            }
                        }else{
                            logger('daily data error');
                            //daily response error, should throw exception
                        }
                    }
                }else{
                    logger('monthly data emtpy');
                    //monthly timeseries empty
                }
            }else{
                logger('monthly data error');
                //monthly reponse error,  should throw exception
            }
            
        }

        $keywords = $keywords->collapse()->mapWithKeys(function($keyword) use ($dataset){
            return [$keyword => $dataset->max(function($data) use ($keyword){
                    return $data['keywords'][$keyword];
                })
            ];
        });

        $maxData = $dataset->max(function($data){
            return $data['value'];
        });

        $dataset = $dataset->map(function($data) use ($keywords, $maxData){
            $data['value'] = (100/$maxData) * $data['value'];
            foreach($keywords as $key=> $value){
                $data['keywords'][$key] = $keywords[$key] != 0 ? (100/$keywords[$key]) * $data['keywords'][$key] : 0;
            }
            return $data;
        });

        $this->currentQueue->update([
            'dataset' => $dataset
        ]);

        dd($dataset->pluck('keywords'), $keywords);

    }

    protected function getPeriods(Collection $dataset){
        $first_date = $dataset->first()['start_date'];
        $last_date = $dataset->last()['end_date'];

        $periods = collect(CarbonPeriod::create($first_date,'8 months', $last_date))->map(function($value){
            return [
                'start_date' => $value->copy(),
                'end_date' => $value->copy()->addMonths(8)->subDays(1)
            ];
        });

        $periods = $periods->filter(function($period) use ($dataset){
            $currentStartDate = $period['start_date'];
            $currentEndDate = $period['end_date'];
            $currentDataSet = $dataset->filter(function($data) use ($currentStartDate, $currentEndDate){
                return $data['start_date']->between($currentStartDate, $currentEndDate) || $data['end_date']->between($currentStartDate, $currentEndDate);
            });
            return $currentDataSet->isNotEmpty();
        });
        
        return $periods;
    }

    protected function normalize(){
        foreach($periods as $period){
            $curentStartPeriod = $period['start_date'];
            $curentEndPeriod = $period['end_date'];
            $reponse = $googleTrend->explore(
                $queue->keywords,
                $queue->category,
                $curentStartPeriod->format('Y-m-d').' '.$curentEndPeriod->format('Y-m-d')
            );
            
            if(is_array($reponse)){ 
                $timeseries = collect($reponse['TIMESERIES']);
                if($timeseries->isNotEmpty()){
                    $timeseries = $timeseries->map(function($data) use ($all){
                        
                    });
                    $dataset = $dataset->map(function($data) use ($timeseries, $keywords){
                        $currentStartDate = $data['start_date'];
                        $currentEndDate = $data['end_date'];
                        $currentTimeSeries = $timeseries->filter(function($time) use($currentStartDate, $currentEndDate){
                            return Carbon::createFromTimestamp($time['time'])->between($currentStartDate, $currentEndDate);
                        });
                        if($currentTimeSeries->isNotEmpty()){
                            if(array_key_exists('trend_data', $data)){
                                for ($i=0; $i < $keywords->count(); $i++) { 
                                    $data['trend_data'][$keywords[$i]] += $currentTimeSeries->sum(function($time) use ($i){
                                        return $time['value'][$i];
                                    });
                                }
                            }else{
                                $data['trend_data'] = [];
                                for ($i=0; $i < $keywords->count(); $i++) { 
                                    $data['trend_data'][$keywords[$i]] = $currentTimeSeries->sum(function($time) use ($i){
                                        return $time['value'][$i];
                                    });
                                }
                            }
                        }else{
                            if(!array_key_exists('trend_data', $data)){
                                $data['trend_data'] = [];
                                foreach($keywords as $keyword){
                                    $data['trend_data'][$keyword] = 0;
                                }
                            }
                        }
                        return $data;
                    });
                }else{
                    $dataset = $dataset->map(function($data) use ($curentStartPeriod, $curentEndPeriod, $keywords){
                        if($data['start_date']->between($curentStartPeriod, $curentEndPeriod) || $data['end_date']->between($curentStartPeriod, $curentEndPeriod)){
                            if(!array_key_exists('trend_data', $data)){
                                $data['trend_data'] = [];
                                foreach($keywords as $keyword){
                                    $data['trend_data'][$keyword] = 0;
                                }
                            }
                        }
                        return $data;
                    });
                }
            }else{
                //error handling reponse not 200
            }
        }
    }



    public function failed(Exception $exception)
    {
        // Send user notification of failure, etc...
    }
}
