<?php namespace Alpaca\Market;

use Alpaca\Market\Alpaca;
use GuzzleHttp\Client;
use Alpaca\Market\Response;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

class Request
{
    
    private $alpaca;

    /**
     * Guzzle Client
     *
     * @return GuzzleHttp\Client
     */
    private $client;

    /**
     * Start the class()
     *
     */
    public function __construct(Alpaca $alpaca, $timeout = 4)
    {
        $this->alpaca = $alpaca;

        $this->client = new Client([
            'base_uri' => $this->alpaca->getRoot(),
            'timeout'  => $timeout
        ]);
    }

    /**
     * send()
     *
     * Send request
     *
     * @return \Alpaca\Market\Response
     */
    public function send($handle, $params = [], $pathParams = [])
    {
        // build and prepare our full path rul
        $url = $this->prepareUrl($handle, $pathParams);

        // lets track how long it takes for this request
        $seconds = 0;

        $auth = base64_encode($this->alpaca->getAuthKeys()[0].':'.$this->alpaca->getAuthKeys()[1]);

        try {
            // push request
            $request = $this->client->request('GET', $url, [
                'query' => $params,
                'headers' => [
                    'Authorization' => 'Basic '.$auth,
                ],
                'on_stats' => function (\GuzzleHttp\TransferStats $stats) use (&$seconds) {
                    $seconds = $stats->getTransferTime(); 
                 }
            ]);
        }
         catch (ClientException  $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $erroObj = json_decode($responseBodyAsString);
            throw new Exception($erroObj->message);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $erroObj = json_decode($responseBodyAsString);
            throw new Exception($erroObj->message);
        }

        // send and return the request response
        return (new Response($request, $seconds));
    }

    /**
     * prepareUrl()
     *
     * Get and prepare the url
     *
     * @return string
     */
    private function prepareUrl($handle, $segments = [])
    {
        $url = $this->alpaca->getPath($handle);

        foreach($segments as $segment=>$value) {
            if (gettype($value) == 'string') {
                $url = str_replace('{'.$segment.'}', $value, $url);
            }
        }

        return $url;
    }
}
