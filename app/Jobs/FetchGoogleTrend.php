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
        $periods = $this->getPeriod($dataset);

        $category = $this->currentQueue->category;
        $fetchedKeywords = collect($this->currentQueue->fetched_keywords);
        $keywords = collect($this->currentQueue->keywords);
        
        if(!$fetchedKeywords->isEmpty() && array_key_exists($category,$fetchedKeywords->toArray())){
            $keywords = $keywords->diff(collect($fetchedKeywords[$category])->pluck('key'));
        }else{
            $fetchedKeywords[$category] = [];
        }
        
        $this->currentQueue->update([
            'status' => 2,
            'updated_at' => $this->currentQueue->updated_at
        ]);

        $i = 0;
        $avaliable = $keywords->count() != collect($this->currentQueue->keywords)->count();
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
                        $keywordDaily = null;
                        // if(!$avaliable && ($i+1) == $keywords->count()){
                        //     // throw new Exception('Error, Google Trends response with empty data for "'.$keyword.'" at period '.$period['start_date']->format('Y-m-d').' - '.$period['end_date']->format('Y-m-d'));
                        //     throw new Exception("Sorry, we can't get enough data");
                        // }
                        break;
                    }
                }else{
                    //daily error;
                    $keywordDaily = null;
                    if(!$avaliable && ($i+1) == $keywords->count()){
                        // throw new Exception('Error while try to fetch "'.$keyword.'" at period '.$period['start_date']->format('Y-m-d').' - '.$period['end_date']->format('Y-m-d'));
                        throw new Exception("Sorry something went wrong try again");
                    }
                    break;
                    
                }
            }
            if($keywordDaily != null){
                $avaliable = true;
                $keywordDaily = $keywordDaily->collapse();
                $dataset = $dataset->map(function($data) use ($keywordDaily, $keyword, $category){
                    $currentStartDate = $data['start_date'];
                    $currentEndDate = $data['end_date'];
                    $total = $keywordDaily->sum(function($data) use ($currentStartDate, $currentEndDate){
                        if($data['time']->between($currentStartDate, $currentEndDate)){
                            return $data['value'];
                        }
                        return 0;
                    });
                    $data['trends'][$category][$keyword] = $total;
                    return $data;
                });
                $max = $dataset->max(function($data) use($keyword,$category){
                    return $data['trends'][$category][$keyword];
                });
                $max = $max == 0 ? 1 : $max;
                $dataset = $dataset->map(function($data) use ($max,$keyword,$category) {
                    $data['trends'][$category][$keyword] = (100/$max) * $data['trends'][$category][$keyword];
                    return $data;
                });

                $relatedQueries = $trend->getRelatedSearchQueries([$keyword],$category, $dataset->first()['start_date']->format('Y-m-d').' '.$dataset->last()['start_date']->format('Y-m-d')); 
                $relatedQueries = collect($relatedQueries[$keyword]['default']['rankedList'][0]['rankedKeyword'])->pluck('query');
                
                $fetchedKeywords[$category] = collect($fetchedKeywords[$category])->push([
                    "key" => $keyword,
                    "avaliable" => true,
                    "related_queries" => $relatedQueries
                ]);

            }
            else{
                $dataset = $dataset->map(function($data) use ($keyword,$category){
                    $data['trends'][$category][$keyword] = null;
                    return $data;
                });
                
                $fetchedKeywords[$category] = collect($fetchedKeywords[$category])->push([
                    "key" => $keyword,
                    "avaliable" => false
                ]);
            }
            $i++;
        }
        

        $this->currentQueue->update([
            'data' => $dataset,
            'fetched_keywords' => $fetchedKeywords,
            'status' => 3,
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

    

    public function failed(Exception $exception)
    {
        $this->currentQueue->update([
            'status' => 0,
            'error_message' => $exception->getMessage()
        ]);
    }

   

}
