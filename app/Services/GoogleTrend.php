<?php
namespace App\Services;

use Google\GTrends;
use Zend\Json\Json;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Zend\Stdlib\Parameters;

class GoogleTrend extends GTrends{
    public function explore($keyWordList, $category=0, $time='today 12-m', $property='', array $widgetIds = ['*'], $sleep=0.5)
    {
        
        if (null !== $keyWordList && ! is_array($keyWordList)) {
            $keyWordList = [$keyWordList];
        }

        if (null === $keyWordList) {
            $comparisonItem[] = ['geo' => $this->options['geo'], 'time' => $time];
        } else {
            if (count($keyWordList) == 0 OR count($keyWordList) > 5) {

                throw new \Exception('Invalid number of items provided in keyWordList');
            }

            $comparisonItem = [];
            foreach ($keyWordList as $kWord) {

                $comparisonItem[] = ['keyword' => $kWord, 'geo' => $this->options['geo'], 'time' => $time];
            }
        }

        $payload = [
            'hl' => $this->options['hl'],
            'tz' => $this->options['tz'],
            'req' => Json::encode(['comparisonItem' => $comparisonItem, 'category' => $category, 'property' => $property]),
        ];

        $data = $this->_getData(self::GENERAL_ENDPOINT, 'GET', $payload);
        

        if (! $data) {

            return false;
        }

        $widgetsArray = Json::decode(trim(substr($data, 5)), Json::TYPE_ARRAY)['widgets'];
        $results = [];
        foreach ($widgetsArray as $widget) {

            $widgetEnabled = false !== array_search('*', $widgetIds) || in_array($widget['id'], $widgetIds, true);

            if (! $widgetEnabled) {

                continue;
            }

            if ($widget['id'] === 'TIMESERIES') {
                $interestOverTimePayload['hl'] = $this->options['hl'];
                $interestOverTimePayload['tz'] = $this->options['tz'];
                $interestOverTimePayload['req'] = Json::encode($widget['request']);
                $interestOverTimePayload['token'] = $widget['token'];

                $data = $this->_getData(self::INTEREST_OVER_TIME_ENDPOINT, 'GET', $interestOverTimePayload);
                if ($data) {

                    $results['TIMESERIES'] = Json::decode(trim(substr($data, 5)), Json::TYPE_ARRAY)['default']['timelineData'];
                } else {

                    $results['TIMESERIES'] = false;
                }
            }

            if (strpos($widget['id'], 'GEO_MAP') === 0) {

                $interestBySubregionPayload['hl'] = $this->options['hl'];
                $interestBySubregionPayload['tz'] = $this->options['tz'];
                $interestBySubregionPayload['req'] = Json::encode($widget['request']);
                $interestBySubregionPayload['token'] = $widget['token'];

                $data = $this->_getData(self::INTEREST_BY_SUBREGION_ENDPOINT, 'GET', $interestBySubregionPayload);
                if ($data) {

                    $queriesArray = Json::decode(trim(substr($data, 5)), Json::TYPE_ARRAY);

                    if (isset($widget['bullets'])) {
                        $queriesArray['bullets'] = $widget['bullets'];
                    }

                    $results['GEO_MAP'][$widget['bullet'] ?? ''] = $queriesArray;
                } else {

                    $results['GEO_MAP'] = false;
                }
            }

            if ($widget['id'] === 'RELATED_QUERIES') {

                $kWord = $widget['request']['restriction']['complexKeywordsRestriction']['keyword'][0]['value'] ?? null;
                $relatedPayload['hl'] = $this->options['hl'];
                $relatedPayload['tz'] = $this->options['tz'];
                $relatedPayload['req'] = Json::encode($widget['request']);
                $relatedPayload['token'] = $widget['token'];
                $data = $this->_getData(self::RELATED_QUERIES_ENDPOINT, 'GET', $relatedPayload);
                if ($data) {

                    $queriesArray = Json::decode(trim(substr($data, 5)), Json::TYPE_ARRAY);

                    if (null === $kWord || count($keyWordList) === 1) {

                        $results['RELATED_QUERIES'] = $queriesArray;
                    } else {

                        $results['RELATED_QUERIES'][$kWord] = $queriesArray;
                    }
                } else {

                    $results['RELATED_QUERIES'] = false;
                }
            }

            if ($widget['id'] === 'RELATED_TOPICS') {
                $relatedPayload['hl'] = $this->options['hl'];
                $relatedPayload['tz'] = $this->options['tz'];
                $relatedPayload['req'] = Json::encode($widget['request']);
                $relatedPayload['token'] = $widget['token'];

                $data = $this->_getData(self::RELATED_QUERIES_ENDPOINT, 'GET', $relatedPayload);
                if ($data) {

                    $results['RELATED_TOPICS'] = Json::decode(trim(substr($data, 5)), Json::TYPE_ARRAY);
                } else {

                    $results['RELATED_TOPICS'] = false;
                }
            }

            usleep($sleep * 1000 * 1000);
        }

        return $results;
    }

    public function _getData($uri, $method, array $params=[])
    {
        if ($method != 'GET' AND $method != 'POST') {

            # throw new \Exception(__METHOD__ . " $method method not allowed");
            die(__METHOD__ . " $method method not allowed");
        }

        $client = new Client();
        $cookieJar = tempnam(storage_path('logs'),'cookie');
        $client->setOptions([
            'adapter' => Curl::class,
            // 'proxy_host' => '36.90.181.227',
            // 'proxy_port' => 80,
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