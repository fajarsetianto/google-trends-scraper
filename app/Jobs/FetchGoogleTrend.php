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
use Exception;


class FetchGoogleTrend implements ShouldQueue
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
    public function handle(GoogleTrend $trend){
        $trend->setOptions([
            'hl'  => 'in',
            'tz'  => -420,
            'geo' => 'ID',
        ]);
        $dataset = collect($this->currentQueue->data)->map(function($data){
            $data['start_date'] = Carbon::parse($data['start_date']);
            $data['end_date'] = Carbon::parse($data['end_date']);
            return $data;
        });
        $fetchedKeywords = collect($this->currentQueue->fetched_keywords);
        $keywords = collect($this->currentQueue->keywords)->diff($fetchedKeywords);
        
        $fetchedKeywords = $fetchedKeywords->merge($keywords);
        
        $periods = $this->getPeriod($dataset);

        $this->currentQueue->update(['status' => 2]);
        foreach($keywords as $keyword){
            $keywordDaily = collect();
            foreach($periods as $period){
                $debugtime= Carbon::now();
                $periodDaily = $trend->interestOverTime($keyword,$this->currentQueue->category, $period['start_date']->format('Y-m-d').' '.$period['end_date']->format('Y-m-d')); 
                logger($keyword.' at '.$period['start_date']->format('Y-m-d').' - '.$period['end_date']->format('Y-m-d').' '.$debugtime->diffInRealSeconds(Carbon::now()).' s');
                if(is_array($periodDaily)){
                    $periodDaily = collect($periodDaily);
                    if($periodDaily->isNotEmpty()){                        
                        $periodDaily = $periodDaily->mapToGroups(function($data){
                          return [
                              Carbon::createFromTimestamp($data['time'])->format('Y-m') => [
                                  'time' => Carbon::createFromTimestamp($data['time']),
                                  'value' => $data['value'][0]
                              ]
                            ];
                        });
                        if($keywordDaily->isNotEmpty()){
                            
                            $oldOverlap = $keywordDaily->intersectByKeys($periodDaily);
                            $newOverlap = $periodDaily->intersectByKeys($oldOverlap);

                            
                            $oldOverlap = $oldOverlap->sum(function($data){
                                return $data->sum('value');
                            });
                            $newOverlap = $newOverlap->sum(function($data){
                                return $data->sum('value');
                            });
                            $scale = (($oldOverlap == 0 ? 1 : $oldOverlap) / ($newOverlap == 0 ? 1 : $newOverlap));
                            
                            $periodDaily = $periodDaily->map(function($grouped) use ($scale){
                                $grouped =  $grouped->map(function($data) use ($scale){
                                    $data['value'] = $scale * $data['value'];
                                    return $data;
                                });
                                return $grouped;
                            });
                        } 
                        $keywordDaily = $keywordDaily->merge($periodDaily);
                        
                    }else{
                        throw new Exception('Error, Google Trends response with empty data for "'.$keyword.'" at period '.$period['start_date']->format('Y-m-d').' - '.$period['end_date']->format('Y-m-d'));
                    }
                }else{
                    //daily error;
                    throw new Exception('Error while try to fetch "'.$keyword.'" at period '.$period['start_date']->format('Y-m-d').' - '.$period['end_date']->format('Y-m-d'));
                }
            }
            $keywordDaily = $keywordDaily->collapse();
            $dataset = $dataset->map(function($data) use ($keywordDaily, $keyword){
                $currentStartDate = $data['start_date'];
                $currentEndDate = $data['end_date'];
                $total = $keywordDaily->sum(function($data) use ($currentStartDate, $currentEndDate){
                    if($data['time']->between($currentStartDate, $currentEndDate)){
                        return $data['value'];
                    }
                    return 0;
                });
                $data['trends'][$keyword] = $total;
                return $data;
            });
            $max = $dataset->max(function($data) use($keyword){
                return $data['trends'][$keyword];
            });
            $dataset = $dataset->map(function($data) use ($max,$keyword) {
                $data['trends'][$keyword] = (100/$max) * $data['trends'][$keyword];
                return $data;
            });
        }
        $this->currentQueue->update(['status' => 3]);

        $this->currentQueue->update([
            'data' => $dataset,
            'fetched_keywords' => $fetchedKeywords,
            'status' => 4,

        ]);
    }

    public function getPeriod(Collection $dataset){
        $first_date = $dataset->first()['start_date']->copy()->firstOfMonth();
        $last_date = $dataset->last()['end_date']->copy()->lastOfMonth();
        if($first_date->diffInMonths($last_date) > 8){
            $periods = collect(CarbonPeriod::create($first_date,'4 months', $last_date))->map(function($value){
                return [
                    'start_date' => $value->copy(),
                    'end_date' => $value->copy()->addMonths(8)->subDays(1)
                ];
            });
        }else{
            $periods = collect([[
                'start_date' => $first_date->copy(),
                'end_date' => $first_date->copy()->addMonths(8)->subDays(1)
            ]]);
        }
        return $periods;
    }

    // public function handle(GoogleTrend $googleTrend)
    // {
    //     /*
    //         Tidak di group karena, ada kemungkinan terdapat range data yang pada kedua periode
    //     */
    //     $googleTrend->setOptions([
    //         'hl'  => 'id',
    //         'tz'  => -420,
    //         'geo' => 'ID',
    //     ]);

    //     $dataset = collect($this->currentQueue->dataset)->map(function($data){
    //         $data['start_date'] = Carbon::parse($data['start_date']);
    //         $data['end_date'] = Carbon::parse($data['end_date']);
    //         return $data;
    //     });

    //     $keywords = collect($this->currentQueue->keywords)->chunk(5)->map(function($data){
    //         return $data->values();
    //     });
    //     // dd($keywords);
    //     $periods = $this->getPeriods($dataset);

    //     $this->currentQueue->update(['status' => 2]);
    //     foreach($keywords as $keyword){
    //         $currentTime = Carbon::now();
    //         $monthly = $googleTrend->explore(
    //             $keyword->toArray(),
    //             $this->currentQueue->category,
    //             'all'
    //         );
    //         logger('monthly => '.$currentTime->diffInSeconds(Carbon::now()));
    //         if(is_array($monthly)){
    //             $monthly = collect($monthly['TIMESERIES']);
    //             if($monthly->isNotEmpty()){
    //                 $monthly = $keyword->count() > 1 ? $this->normalize($monthly,$keyword) : $monthly;
    //                 $monthly = $monthly->mapWithKeys(function($data){
    //                     return [Carbon::createFromTimestamp($data['time'])->format('Y-m') => $data];
    //                 });
    //                 dd($monthly->pluck('value.0'),$monthly->pluck('value.1'),$monthly->pluck('value.2'),$monthly->pluck('value.3'));
    //                 foreach($periods as $period){
    //                     $currentTime = Carbon::now();
    //                     $daily = $googleTrend->explore(
    //                         $keyword->toArray(),
    //                         $this->currentQueue->category,
    //                         $period['start_date']->format('Y-m-d').' '.$period['end_date']->format('Y-m-d')
    //                     );
    //                     logger('period => '.$currentTime->diffInSeconds(Carbon::now()));
    //                     $currentTime = Carbon::now();
    //                     if(is_array($daily)){
    //                         $daily = collect($daily['TIMESERIES']);
    //                         if($daily->isNotEmpty()){
    //                             //group daily data according to its month and year
    //                             $daily = $daily->groupBy(function($data){
    //                                 return Carbon::createFromTimestamp($data['time'])->format('Y-m');
    //                             });
                                

    //                             $daily = $daily->map(function($data) use ($keyword){
    //                                 return $this->normalize($data, $keyword);
    //                             });
                                
    //                             // normalize daily data against monthly data;
    //                             $formatedDaily = [];
    //                             //lopping troguh daily group as month
    //                             foreach($daily as $month){
    //                                 //lopping trough keyword
    //                                 foreach($keyword as $key => $term){
    //                                     //sum all daily record for current keyword
    //                                     $totalDailyKeyword = $month->sum(function($daily) use ($key){
    //                                         return $daily['value'][$key];
    //                                     });
    //                                     //lopping trough grouped daily
    //                                     foreach($month as $day){
    //                                         $currentDay = Carbon::createFromTimestamp($day['time']);
    //                                         if($totalDailyKeyword == 0 || $monthly[$currentDay->format('Y-m')]['value'][$key] == 0){
    //                                             $formatedDaily[$currentDay->format('Y-m-d')]['value'][$key] = 0;
    //                                         }else{
    //                                             $formatedDaily[$currentDay->format('Y-m-d')]['value'][$key] = ($monthly[$currentDay->format('Y-m')]['value'][$key] / $totalDailyKeyword) * $day['value'][$key];
    //                                         }
    //                                     }
    //                                 }
    //                             }

    //                             //need to be find the best algorithm for saving this varaible bellow
    //                             $dataset = $dataset->map(function($data) use ($formatedDaily, $keyword){
    //                                 $currentStartDate = $data['start_date'];
    //                                 $currentEndDate = $data['end_date'];
    //                                 //find daily data that in range data
    //                                 $currentData = collect($formatedDaily)->filter(function($data ,$key) use ($currentStartDate, $currentEndDate){
    //                                     return Carbon::parse($key)->between($currentStartDate, $currentEndDate);
    //                                 });
    //                                 if($currentData->isNotEmpty()){
    //                                     foreach($keyword as $key=>$term){
    //                                         $totalCurent = $currentData->sum(function($data) use ($key){
    //                                             return $data['value'][$key];
    //                                         });
    //                                         $data['keywords'][$term] = isset($data['keywords'][$term]) ? $data['keywords'][$term] + $totalCurent: $totalCurent; 
                                            
    //                                     }
    //                                 }else{
    //                                     //current data empty
    //                                 }
    //                                 return $data;
    //                             });
    //                         }else{
    //                             //daily timeseries empty
    //                             logger('daily data empty');
    //                             //need to be find the best algorithm for saving this varaible bellow
    //                             $dataset = $dataset->map(function($data) use ($period, $keyword){
    //                                 if(!isset($data['keywords'][$keyword[0]])){
    //                                     if($data['start_date']->between($period['start_date'],$period['end_date']) ||
    //                                         $data['end_date']->between($period['start_date'],$period['end_date'])
    //                                     ){
    //                                         foreach($keyword as $value){
    //                                             $data['keywords'][$value] = 0;
    //                                         }
    //                                     }
    //                                 }
    //                                 return $data;
    //                             });
    //                         }
    //                     }else{
    //                         logger('daily data error');
    //                         //daily response error, should throw exception
    //                     }
    //                     logger('arange data => '.$currentTime->diffInSeconds(Carbon::now()));
    //                 }
    //             }else{
                    
    //                 throw new Exception('monthly data empty');
    //                 logger('monthly data emtpy');
    //                 //monthly timeseries empty
    //             }
    //         }else{
                
    //             throw new Exception('monthly data error');
    //             logger('monthly data error');
    //             //monthly reponse error,  should throw exception
    //         }
            
    //     }
        
    //     $this->currentQueue->update(['status' => 3]);

    //     $currentTime = Carbon::now();// debug
    //     $keywords = $keywords->collapse()->mapWithKeys(function($keyword) use ($dataset){
    //         return [$keyword => $dataset->max(function($data) use ($keyword){
    //                 return $data['keywords'][$keyword];
    //             })
    //         ];
    //     });

    //     $maxData = $dataset->max(function($data){
    //         return $data['value'];
    //     });

    //     $dataset = $dataset->map(function($data) use ($keywords, $maxData){
    //         $data['value'] = (100/$maxData) * $data['value'];
    //         foreach($keywords as $key=> $value){
    //             $data['keywords'][$key] = $keywords[$key] != 0 ? (100/$keywords[$key]) * $data['keywords'][$key] : 0;
    //         }
    //         return $data;
    //     });

    //     $corelation = collect();

    //     $realCases = $dataset->pluck('value');
    //     $n = $realCases->count();
    //     $sigmaX = $realCases->sum();
    //     $sigmaX2 = $realCases->sum(function($value){
    //         return pow($value,2);
    //     });
    //     foreach(collect($this->currentQueue->keywords) as $keyword){
    //         $currentTrendData = $dataset->pluck('keywords.'.$keyword);                    
    //         $sigmaY = $currentTrendData->sum();
    //         $sigmaY2 = $currentTrendData->sum(function($value){
    //             return pow($value,2);
    //         });
    //         $sigmaXY = $currentTrendData->zip($realCases)->sum(function($item){
    //             return $item[0] * $item[1];
    //         });
    //         $top = ($sigmaXY - (($sigmaX * $sigmaY) / $n));
    //         $bottom = sqrt(($sigmaX2 - (pow($sigmaX,2) / $n)) * ($sigmaY2 - (pow($sigmaY,2) / $n)));
                    
    //         $corelation[$keyword] = $top / ($bottom != 0 ? $bottom : 1);
    //         // $corelation[$keyword] = ($sigmaXY - (($sigmaX * $sigmaY) / $n)) / (sqrt(($sigmaX2 - ((pow($sigmaX,2) / $n) ?? 1)) * ($sigmaY2 - ((pow($sigmaY,2) / $n) ?? 1))) ?? 1);
    //     }
    //     logger('normalization data => '.$currentTime->diffInSeconds(Carbon::now()));//debug
    //     $this->currentQueue->update([
    //         'dataset' => $dataset,
    //         'corelation' => $corelation,
    //         'status' => 4
    //     ]);

    // }
    

    // protected function getPeriods(Collection $dataset){
    //     $first_date = $dataset->first()['start_date'];
    //     $last_date = $dataset->last()['end_date'];

    //     $periods = collect(CarbonPeriod::create($first_date,'8 months', $last_date))->map(function($value){
    //         return [
    //             'start_date' => $value->copy(),
    //             'end_date' => $value->copy()->addMonths(8)->subDays(1)
    //         ];
    //     });

    //     $periods = $periods->filter(function($period) use ($dataset){
    //         $currentStartDate = $period['start_date'];
    //         $currentEndDate = $period['end_date'];
    //         $currentDataSet = $dataset->filter(function($data) use ($currentStartDate, $currentEndDate){
    //             return $data['start_date']->between($currentStartDate, $currentEndDate) || $data['end_date']->between($currentStartDate, $currentEndDate);
    //         });
    //         return $currentDataSet->isNotEmpty();
    //     });
        
    //     return $periods;
    // }

    public function failed(Exception $exception)
    {
        $this->currentQueue->update(['status' => 0]);
    }

    // protected function normalize($dataset, $keyword){
        
    //     $max = collect();
    //     foreach($keyword as $key => $value){
    //         $temp = $dataset->max('value.'.$key);
    //         $max[$key] = $temp == 0 ? 1 : $temp;
    //     }
    //     $dataset = $dataset->map(function($data) use ($max){
    //         foreach($max as $key => $value){
    //             $data['value'][$key] = (100/$value) * $data['value'][$key];
    //         }
    //         return $data;
    //     });
    //     return $dataset;

    // }

}
