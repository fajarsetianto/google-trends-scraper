<?php
namespace App\Services;

// use Goutte\Client;
use Campo\UserAgent;
use ScraperAPI\Client;

class Trend {
    private $config, $geo, $token, $client;

    public function __construct($timezone = null,$language = null,$geo = null){
        $this->config = [
            'hl'=> 'in',
            'tz' => '-420',
            
        ];
        $this->geo = 'ID';
        $this->client = new Client('92ef600e86d13bf3fe4dddb3e039fb08');
    }

    public function multiline($keyword,$category,$time){
        dd($this->_getToken($keyword,$time));
    }

    protected function _getToken($keyword,$time){
        // $this->_setUserAgent();
        $params = $this->config;
        $params['req'] = json_encode([
            'comparisonItem' => [
                [
                    'keyword' => $keyword,
                    'geo' => $this->geo,
                    'time' => $time,
                ],
            ],
            'category' => 0,
            'property' => '',
        ]);
        $toManyRequest = 0;
        for ($i=0; $i < 200 ; $i++) { 
            $results = $this->client->get('https://trends.google.com/trends/api/explore?'.http_build_query($params),[
                'headers' => [],
                'country_code' => 'US',
                'device_type' => null,
                'premium' => false,
                'render' => false,
                'session_number' => null,
                'autoparse' => false,
                'retry' => 10,
                'timeout' => 60
            ])->raw_body; 
            dd($results);
            if($this->client->getResponse()->getStatusCode() != 200){
                dd($this->client->getResponse()->getContent());
                if(--$toManyRequest <= 0){
                    dd('iterate over '.$i.' times', 'status code => '.$this->client->getResponse()->getStatusCode());
                }
            }
            
            // usleep(0.5 * 1000 * 1000);
        }
        
        dd($i);
    }

    protected function _setUserAgent(){
        $this->client->setServerParameter('HTTP_USER_AGENT', UserAgent::random());
    }
}