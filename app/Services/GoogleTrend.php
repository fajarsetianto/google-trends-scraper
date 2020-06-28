<?php
namespace App\Services;

use Google\GTrends;
use Zend\Json\Json;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Zend\Stdlib\Parameters;

class GoogleTrend extends GTrends{


    
    public function interestOverTime($kWord, $category=0, $time='now 4-H', $property='')
    {
        $payload = [
            'hl' => $this->options['hl'],
            'tz' => $this->options['tz'],
            'req' => Json\Json::encode([
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

            $widgets = Json\Json::decode(trim(substr($data, 4)), Json\Json::TYPE_OBJECT)->widgets;

            foreach ($widgets as $widget) {

                if ($widget->id == 'TIMESERIES') {

                    $interestOverTimePayload['hl'] = $this->options['hl'];
                    $interestOverTimePayload['tz'] = $this->options['tz'];
                    $interestOverTimePayload['req'] = Json\Json::encode($widget->request);
                    $interestOverTimePayload['token'] = $widget->token;

                    $data = $this->_getData(self::INTEREST_OVER_TIME_ENDPOINT, 'GET', $interestOverTimePayload);
                    if ($data) {

                        return Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY)['default']['timelineData'];
                    } else {
                        logger($data);
                        return false;
                    }
                }
            }
        }

        return false;
    }


    public function _getData($uri, $method, array $params=[])
    {
        if ($method != 'GET' AND $method != 'POST') {

            # throw new \Exception(__METHOD__ . " $method method not allowed");
            die(__METHOD__ . " $method method not allowed");
        }

        $client = new Client();
        $cookieJar = tempnam(storage_path('tmp'),'cookie');
        $client->setOptions([
            'adapter' => Curl::class,
            // 'proxy_host' => $this->proxy['ip'],
            // 'proxy_port' => $this->proxy['port'],
            'curloptions' => [
                CURLOPT_COOKIEJAR => $cookieJar,
            ],
            'maxredirects' => 10,
            'timeout' => 100]);
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