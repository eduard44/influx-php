<?php

/**
 * InfluxDB PHP Client
 *
 * Modified fork of https://github.com/crodas/InfluxPHP
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 */

namespace Chromabits\InfluxClient;

use GuzzleHttp\Client as HttpClient;

/**
 * Class BaseHttp
 *
 * Base HTTP Library for client operations
 *
 * @package Chromabits\InfluxClient
 */
class BaseHttp
{
    const SECOND = 's';
    const MILLISECOND = 'm';
    const MICROSECOND = 'u';

    const S = 's';
    const MS = 'm';
    const US = 'u';

    /**
     * Server hostname
     *
     * @var string
     */
    protected $host;

    /**
     * Server port
     *
     * @var int
     */
    protected $port;

    /**
     * HTTP auth username
     *
     * @var string
     */
    protected $user;

    /**
     * HTTP auth password
     *
     * @var string
     */
    protected $pass;

    /**
     * Base path for URLs
     *
     * @var string
     */
    protected $base;

    /**
     * Protocol to use
     *
     * Should either be http or https
     *
     * @var string
     */
    protected $protocol;

    /**
     * Time precision to use on each request
     *
     * @var string
     */
    protected $timePrecision = 's';

    /**
     * Children objects that have inherited this
     *
     * @var array
     */
    protected $children = [];

    /**
     * Internal HTTP client
     *
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * Construct an instance of a BaseHttp
     */
    public function __construct()
    {
        $this->protocol = 'http';

        $this->host = 'localhost';

        $this->base = '';
    }

    /**
     * Get the time precision
     *
     * @return string
     */
    public function getTimePrecision()
    {
        return $this->timePrecision;
    }

    /**
     * Set the time precision
     *
     * @param $precision
     *
     * @return $this
     */
    public function setTimePrecision($precision)
    {
        switch ($precision) {
            case 'm':
            case 's':
            case 'u':
                $this->timePrecision = $precision;

                if ($this instanceof Client) {
                    foreach ($this->children as $children) {
                        $children->timePrecision = $precision;
                    }
                }

                return $this;
        }

        throw new \InvalidArgumentException("Expecting s, m or u as time precision");
    }

    /**
     * Inherit properties from another BaseHttp object
     *
     * @param \Chromabits\InfluxClient\BaseHttp $parent
     */
    protected function inherits(BaseHTTP $parent)
    {
        $this->user = $parent->user;
        $this->pass = $parent->pass;
        $this->port = $parent->port;
        $this->host = $parent->host;
        $this->protocol = $parent->protocol;
        $this->timePrecision = $parent->timePrecision;

        $parent->children[] = $this;
    }

    /**
     * Setup the internal HTTP client
     */
    protected function setupClient()
    {
        $this->httpClient = new HttpClient([
            'base_url' => [
                $this->protocol . '://{host}:{port}/',
                [
                    'host' => $this->host,
                    'port' => $this->port,
                    'path' => $this->base
                ]
            ],
            'defaults' => [
                'auth' => [$this->user, $this->pass]
            ]
        ]);
    }

    /**
     * Perform a DELETE HTTP request
     *
     * @param $url
     *
     * @return \GuzzleHttp\Message\ResponseInterface|mixed|null
     */
    protected function delete($url)
    {
        return $this->httpClient->delete($url);
    }

    /**
     * Perform a HTTP GET request
     *
     * @param $url
     * @param array $args
     *
     * @return \GuzzleHttp\Message\ResponseInterface|mixed|null
     */
    protected function get($url, array $args = [])
    {
        $response = $this->httpClient->get($url, [
            'query' => $args
        ]);

        return $response;
    }

    /**
     * Perform a HTTP POST request
     *
     * @param $url
     * @param array $body
     * @param array $args
     *
     * @return \GuzzleHttp\Message\ResponseInterface|mixed|null
     */
    protected function post($url, array $body, array $args = [])
    {
        return $this->httpClient->post($url, [
            'headers' => [
                'content-type' => 'application/json',
            ],
            'body' => json_encode($body),
            'query' => $args
        ]);
    }
}
