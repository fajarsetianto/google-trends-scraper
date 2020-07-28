<?php
namespace App\Services;

use Exception;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\Cookies;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Cookie\CookieJar;

class GoogleTrend{

    private $options;

    public function __construct($geo_code, $lang_code, $timezone)
    {
        $this->options = [
            'geo' => $geo_code,
            'hl' => $lang_code,
            'tz' => $timezone
        ];
    }

    public function prepare($widget,$keyword, $category, $time){
        $params = [
            'hl' => $this->options['hl'],
            'tz' => $this->options['tz'],
            'req' => json_encode([
                'comparisonItem' => [
                    [
                        'keyword' => $keyword,
                        'geo' => $this->options['geo'],
                        'time' => $time,
                    ],
                ],
                'category' => $category,
            ]),
        ];
        $data = $this->_getData('https://trends.google.com/trends/api/explore',$params);
        if($data){
            $widgets = collect(json_decode(trim(substr($data, 4)), true)['widgets']);
            $currentWidget = $widgets->where('id', $widget);
            if($currentWidget->isNotEmpty()){
                return [
                    'token' => $currentWidget->first()['token'],
                    'req' => json_encode($currentWidget->first()['request'])
                ];
            }
        }
        return false;
    }

    public function getMultilineData($keyword, $category, $time){
        $data = $this->prepare('TIMESERIES', $keyword, $category, $time);
        if($data){
            $data['hl'] = $this->options['hl'];
            $data['tz'] = $this->options['tz'];
            $data = $this->_getData('https://trends.google.com/trends/api/widgetdata/multiline', $data);
            if($data){
                return json_decode(trim(substr($data, 5)), true)['default']['timelineData'];
            }
        }
        return false;
        
    }

    public function _getData($url, $params){
        $client = new GuzzleClient();
        $jar = new \GuzzleHttp\Cookie\FileCookieJar(storage_path('tmp').'/gtrendcookie.txt' ,true);
        $response = $client->request('GET', $url, ['query' => $params,'cookies' => $jar,'proxy' => 'http://144.168.240.98:19999']);
        if ($response->getStatusCode() == 200) {
            return $response->getBody()->getContents();
        }else{
            return false;
        }

        // $client = new Client();
        // // $cookieJar = tempnam(storage_path('tmp'),'cookie');
        // $client
        //     ->setOptions([
        //         'adapter' => Curl::class,
        //         'proxy_host' => 'p.webshare.io',
        //         'proxy_user'=> 'edswurpo-rotate',
        //         'proxy_pass' => 'qh30oorwzasa',
        //         'proxy_port' => '80',
        //         'curloptions' => [
        //             // CURLOPT_COOKIEJAR => $cookieJar,
        //             CURLOPT_SSL_VERIFYPEER => false,
        //         ],
        //         'maxredirects' => 10,
        //         'timeout' => 60])
        //     ->setUri($url)
        //     ->setMethod('GET')
        //     ->setParameterGet($params);
        // $cookies= Cache::get('gtrendCookie', new Cookies());
        
        // if(!($cookies instanceof Cookies)){
        //     $newCookies = new Cookie();
        //     foreach ($cookies as $cookie) {
        //         $newCookies->addCookie($cookie);
        //     }
        //     $cookies = $newCookies;
        // }else{
        //     if(collect($cookies->getAllCookies())->isEmpty()){
        //         $response = $client->send();
        //         $cookies->addCookiesFromResponse($response, $client->getUri());
        //     }
        // }
        
        
        // $client->setCookies($cookies->getMatchingCookies($url));
        // $client->send();
        // $cookies->addCookiesFromResponse($response, $client->getUri());
        // // dd($cookies);
        // Cache::forever('gtrendCookie', json_encode($cookies->getAllCookies(1)));
        
        // $client->send();
        
        // $statusCode = $client->getResponse()->getStatusCode();
        
        // if ($statusCode == 200) {
        //     return $client->getResponse()->getBody();
        // }else{
        //     return false;
        // }
    }

    public function suggestionsAutocomplete($keyword)
    {
        $param = ['hl' => $this->options['hl']];
        $data = $this->_getData('https://trends.google.com/trends/api/autocomplete/'.$keyword, $param);
        if ($data) {
            return json_decode(trim(substr($data, 5)), true);
        }
        return false;
    }

    public function getCategories()
    {
        $params = ['hl' => $this->options['hl']];
        $data = $this->_getData('https://trends.google.com/trends/api/explore/pickers/category', $params);
        if ($data) {
            return json_decode(trim(substr($data, 5)), true);
        }
        return false;
    }

    public function getRelatedSearchQueries($keyword, $category, $time){
        $data = $this->prepare('RELATED_QUERIES', $keyword, $category, $time);
        if($data){
            $data['hl'] = $this->options['hl'];
            $data['tz'] = $this->options['tz'];
            $data = $this->_getData('https://trends.google.com/trends/api/widgetdata/relatedsearches', $data);
            if($data){
                return json_decode(trim(substr($data, 5)), true);
            }
        }
        return false;
        
    }

    
}