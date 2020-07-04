<?php
namespace App\Services;

use Google\GTrends;
use Laminas\Json\Json;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\Client\Adapter\Socket;
use Laminas\Http\Client\Adapter\Proxy;
use Zend\Stdlib\Parameters;

class GoogleTrend extends GTrends{


    
    public function interestOverTime($kWord, $category=0, $time='now 4-H', $property='')
    {
        $payload = [
            'hl' => $this->options['hl'],
            'tz' => $this->options['tz'],
            'req' => Json::encode([
                'comparisonItem' => [
                    [
                        'keyword' => $kWord,
                        'geo' => $this->options['geo'],
                        'time' => $time,
                    ],
                ],
                'category' => $category,
                'property' => $property,
            ]),
        ];
        $data = $this->_getData(self::GENERAL_ENDPOINT, 'GET', $payload);
        if ($data) {

            $widgets = Json::decode(trim(substr($data, 4)), Json::TYPE_OBJECT)->widgets;

            foreach ($widgets as $widget) {

                if ($widget->id == 'TIMESERIES') {

                    $interestOverTimePayload['hl'] = $this->options['hl'];
                    $interestOverTimePayload['tz'] = $this->options['tz'];
                    $interestOverTimePayload['req'] = Json::encode($widget->request);
                    $interestOverTimePayload['token'] = $widget->token;

                    $data = $this->_getData(self::INTEREST_OVER_TIME_ENDPOINT, 'GET', $interestOverTimePayload);
                    if ($data) {

                        return Json::decode(trim(substr($data, 5)), Json::TYPE_ARRAY)['default']['timelineData'];
                    } else {
                        logger($data);
                        return false;
                    }
                }
            }
        }
        logger($data);
        return false;
    }


    public function _getData($uri, $method, array $params=[])
    {
        // $params['premium'] = true;
        // $params['country_code'] = "US";
        if ($method != 'GET' AND $method != 'POST') {

            # throw new \Exception(__METHOD__ . " $method method not allowed");
            die(__METHOD__ . " $method method not allowed");
        }

        $client = new Client();
        $cookieJar = tempnam(storage_path('tmp'),'cookie');
        $client->setOptions([
            'adapter' => Curl::class,
            'proxy_host' => 'p.webshare.io',
            'proxy_user'=> 'edswurpo-rotate',
            'proxy_pass' => 'qh30oorwzasa',
            'proxy_port' => '1080',
            'curloptions' => [
                CURLOPT_COOKIEJAR => $cookieJar,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_PROXYTYPE => 7
                // CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5
            ],
            'maxredirects' => 10,
            'timeout' => 60]);
        $client->setUri($uri);
        $client->setMethod(strtoupper($method));

        if (strtoupper($method) == 'POST') {

            $client->getRequest()->getHeaders()->addHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);
        }

        if ($params) {

            if (strtoupper($method) == 'GET') {

                $client->setParameterGet($params);
            }

            if (strtoupper($method) == 'POST') {

                $client->getRequest()->setQuery(new Parameters($params));
            }
        }

        $client->send();
        $client->setOptions([
            'curloptions' => [
                CURLOPT_COOKIEFILE => $cookieJar,
            ]]);
        $client->send();
        unlink($cookieJar);

        $statusCode = $client->getResponse()->getStatusCode();
        if ($statusCode == 200) {

            $headers = $client->getResponse()->getHeaders()->toArray();
            foreach ($headers as $header => $value) {

                if ($header == 'Content-Type') {

                    if (
                        (stripos($value, 'application/json') !== false OR
                            stripos($value, 'application/javascript') !== false OR
                            stripos($value, 'text/javascript') !== false) AND $client->getResponse()->getBody()
                    ) {

                        return $client->getResponse()->getBody();
                    }
                }
            }
        }
        return false;
    }

    public function suggestionsAutocomplete($kWord)
    {
        $uri = self::SUGGESTIONS_AUTOCOMPLETE_ENDPOINT . "/'$kWord'";
        $param = ['hl' => $this->options['hl']];
        $data = $this->_getData($uri, 'GET', $param);
        if ($data) {

            return Json::decode(trim(substr($data, 5)), Json::TYPE_ARRAY);
        }
        return false;
    }

    public function getCategories()
    {
        $uri = self::CATEGORIES_ENDPOINT;
        $param = ['hl' => $this->options['hl']];
        $data = $this->_getData($uri, 'GET', $param);
        if ($data) {

            return Json::decode(trim(substr($data, 5)), Json::TYPE_ARRAY);
        }
        return false;
    }
    
}