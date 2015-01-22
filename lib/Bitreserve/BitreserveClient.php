<?php

namespace Bitreserve;

use Bitreserve\Exception\InvalidArgumentException;
use Bitreserve\Exception\MissingArgumentException;
use Bitreserve\Exception\RuntimeException;
use Bitreserve\HttpClient\HttpClient;
use Bitreserve\HttpClient\HttpClientInterface;
use Bitreserve\HttpClient\Message\ResponseMediator;
use Bitreserve\Model\Ticker;
use Bitreserve\Model\Token;
use Bitreserve\Model\Transaction;

/**
* Bitreserve API client.
*/
class BitreserveClient
{
    /**
     * Guzzle instance used to communicate with Bitreserve.
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var array
     */
    private $options = array(
        'api_version' => 'v0',
        'base_url' => 'https://api.bitreserve.org/',
        'debug' => false,
        'timeout' => 10,
        'user_agent' => 'bitreserve-sdk-php v1.0.0 (https://github.com/seegno/bitreserve-sdk-php)',
    );

    /**
     * Current Token object.
     *
     * @var Token
     */
    private $token;

    /**
     * Constructor.
     *
     * @param string|null $bearer Authorization Token.
     */
    public function __construct($bearer = null)
    {
        $this->options = array_merge($this->options, array('bearer' => $bearer));

        $this->setHttpClient(new HttpClient($this->options));
    }

    /**
     * Gets Http client.
     *
     * @return HttpClientInterface $httpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Gets client option.
     *
     * @param  string $name Option name.
     *
     * @return mixed        Option value.
     */
    public function getOption($name)
    {
        if (!isset($this->options[$name])) {
            return null;
        }

        return $this->options[$name];
    }

    /**
     * Gets all client options.
     *
     * @return array An array of options.
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param HttpClientInterface $httpClient
     */
    public function setHttpClient(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Sets client option.
     *
     * @param string $name  Option name.
     * @param mixed $value Option value.
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Retrieve all available currencies.
     *
     * @return array The list of all available currencies.
     */
    public function getCurrencies()
    {
        $tickers = $this->getTicker();

        return array_reduce($tickers, function($currencies, $ticker) {
            if (in_array($ticker->getCurrency(), $currencies)) {
                return $currencies;
            }

            $currencies[] = $ticker->getCurrency();

            return $currencies;
        }, array());
    }

    /**
     * Retrieve all exchanges rates for all currency pairs.
     *
     * @return array The current rates as provided by the ticker.
     */
    public function getTicker()
    {
        $data = $this->get('/ticker');

        return array_map(function($ticker) {
            return new Ticker($this, $ticker);
        }, $data);
    }

    /**
     * Retrieve all exchanges rates relative to a given currency.
     *
     * @return array The current rates for the currency specific as provided by the ticker.
     */
    public function getTickerByCurrency($currency)
    {
        $data = $this->get(sprintf('/ticker/%s', rawurlencode($currency)));

        return array_map(function($ticker) {
            return new Ticker($this, $ticker);
        }, $data);
    }

    /**
     * Gets the current token or create a new one.
     *
     * @return Token A token object.
     */
    public function getToken()
    {
        if ($this->token) {
            return $this->token;
        }

        $this->token = new Token($this);

        return $this->token;
    }

    /**
     * Return the public view of any transaction.
     * Be advised that this method has the potential to return a great deal of data.
     *
     * @return Transaction The transaction identified by a given id.
     */
    public function getTransactionById($id)
    {
        $data = $this->get(sprintf('/reserve/transactions/%s', $id));

        return new Transaction($this, $data);
    }

    /**
     * Return the public view of all transactions from the beginning of time.
     * Be advised that this method has the potential to return a great deal of data.
     *
     * @return array The list all public transactions.
     */
    public function getTransactions()
    {
        $data = $this->get('/reserve/transactions');

        return array_map(function($transaction) {
            return new Transaction($this, $transaction);
        }, $data);
    }

    /**
     * Send a GET request with query parameters.
     *
     * @param string $path              Request path.
     * @param array $parameters         GET parameters.
     * @param array $requestHeaders     Request Headers.
     *
     * @return \GuzzleHttp\EntityBodyInterface|mixed|string
     */
    public function get($path, array $parameters = array(), $requestHeaders = array())
    {
        $response = $this->getHttpClient()->get(
            $path,
            $parameters,
            array_merge($this->getDefaultHeaders(), $requestHeaders)
        );

        return ResponseMediator::getContent($response);
    }

    /**
     * Send a POST request with JSON-encoded parameters.
     *
     * @param string $path              Request path.
     * @param array $parameters         POST parameters to be JSON encoded.
     * @param array $requestHeaders     Request headers.
     *
     * @return \GuzzleHttp\EntityBodyInterface|mixed|string
     */
    public function post($path, array $parameters = array(), $requestHeaders = array())
    {
        $response = $this->getHttpClient()->post(
            $path,
            $this->createJsonBody($parameters),
            array_merge($this->getDefaultHeaders(), $requestHeaders)
        );

        return ResponseMediator::getContent($response);
    }

    /**
     * Send a PATCH request with JSON-encoded parameters.
     *
     * @param string $path              Request path.
     * @param array $parameters         POST parameters to be JSON encoded.
     * @param array $requestHeaders     Request headers.
     *
     * @return \GuzzleHttp\EntityBodyInterface|mixed|string
     */
    public function patch($path, array $parameters = array(), $requestHeaders = array())
    {
        $response = $this->getHttpClient()->patch(
            $path,
            $this->createJsonBody($parameters),
            array_merge($this->getDefaultHeaders(), $requestHeaders)
        );

        return ResponseMediator::getContent($response);
    }

    /**
     * Send a PUT request with JSON-encoded parameters.
     *
     * @param string $path              Request path.
     * @param array $parameters         POST parameters to be JSON encoded.
     * @param array $requestHeaders     Request headers.
     *
     * @return \GuzzleHttp\EntityBodyInterface|mixed|string
     */
    public function put($path, array $parameters = array(), $requestHeaders = array())
    {
        $response = $this->getHttpClient()->put(
            $path,
            $this->createJsonBody($parameters),
            array_merge($this->getDefaultHeaders(), $requestHeaders)
        );

        return ResponseMediator::getContent($response);
    }

    /**
     * Send a DELETE request with JSON-encoded parameters.
     *
     * @param string $path              Request path.
     * @param array $parameters         POST parameters to be JSON encoded.
     * @param array $requestHeaders     Request headers.
     *
     * @return \GuzzleHttp\EntityBodyInterface|mixed|string
     */
    public function delete($path, array $parameters = array(), $requestHeaders = array())
    {
        $response = $this->getHttpClient()->delete(
            $path,
            $this->createJsonBody($parameters),
            array_merge($this->getDefaultHeaders(), $requestHeaders)
        );

        return ResponseMediator::getContent($response);
    }

    /**
     * Create a JSON encoded version of an array of parameters.
     *
     * @param array $parameters Request parameters
     *
     * @return null|string The parameters in JSON format.
     */
    protected function createJsonBody(array $parameters)
    {
        return (count($parameters) === 0) ? null : json_encode($parameters, empty($parameters) ? JSON_FORCE_OBJECT : 0);
    }

    /**
     * Create the API default headers that are mandatory.
     *
     * @return array The list of default headers.
     */
    protected function getDefaultHeaders()
    {
        $headers = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => $this->getOption('user_agent'),
        );

        if (!empty($this->getOption('bearer'))) {
            $headers['Authorization'] = sprintf('Bearer %s', $this->getOption('bearer'));
        }

        return $headers;
    }
}