<?php

namespace App\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationCredentials;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationMiddleware;
use webignition\Guzzle\Middleware\RequestHeaders\RequestHeadersMiddleware;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class HttpClientService
{
    const MIDDLEWARE_RETRY_KEY = 'retry';
    const MIDDLEWARE_HISTORY_KEY = 'history';
    const MIDDLEWARE_HTTP_AUTH_KEY = 'http-auth';
    const MIDDLEWARE_REQUEST_HEADERS_KEY = 'request-headers';

    const MAX_RETRIES = 5;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var array
     */
    private $curlOptions;

    /**
     * @var HttpHistoryContainer
     */
    private $historyContainer;

    /**
     * @var HttpAuthenticationMiddleware
     */
    private $httpAuthenticationMiddleware;

    /**
     * @var CookieJarInterface
     */
    private $cookieJar;

    /**
     * @var RequestHeadersMiddleware
     */
    private $requestHeadersMiddleware;

    /**
     * @var HandlerStack
     */
    protected $handlerStack;

    /**
     * @param array $curlOptions
     */
    public function __construct(array $curlOptions)
    {
        $this->setCurlOptions($curlOptions);
        $this->historyContainer = new HttpHistoryContainer();
        $this->httpAuthenticationMiddleware = new HttpAuthenticationMiddleware();
        $this->cookieJar = new CookieJar();
        $this->requestHeadersMiddleware = new RequestHeadersMiddleware();
        $this->handlerStack = HandlerStack::create($this->createInitialHandler());

        $this->httpClient = $this->create();
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @return HttpHistoryContainer
     */
    public function getHistory()
    {
        return $this->historyContainer;
    }

    /**
     * Set cookies to be sent on all requests (dependent on cookie domain/secure matching rules)
     *
     * @param SetCookie[] $cookies
     */
    public function setCookies($cookies = [])
    {
        $this->clearCookies();

        if (empty($cookies)) {
            return;
        }

        foreach ($cookies as $cookie) {
            $this->cookieJar->setCookie($cookie);
        }
    }

    public function clearCookies()
    {
        $this->cookieJar->clear();
    }

    /**
     * @param HttpAuthenticationCredentials $httpAuthenticationCredentials
     */
    public function setBasicHttpAuthorization(HttpAuthenticationCredentials $httpAuthenticationCredentials)
    {
        $this->httpAuthenticationMiddleware->setHttpAuthenticationCredentials($httpAuthenticationCredentials);
    }

    public function clearBasicHttpAuthorization()
    {
        $this->httpAuthenticationMiddleware->setHttpAuthenticationCredentials(new HttpAuthenticationCredentials());
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setRequestHeader($name, $value)
    {
        $this->requestHeadersMiddleware->setHeader($name, $value);
    }

    /**
     * @param array $curlOptions
     */
    private function setCurlOptions(array $curlOptions)
    {
        $definedCurlOptions = [];

        foreach ($curlOptions as $name => $value) {
            if (defined($name)) {
                $definedCurlOptions[constant($name)] = $value;
            }
        }

        $this->curlOptions = $definedCurlOptions;
    }

    /**
     * @return HttpClient
     */
    private function create()
    {
        $this->handlerStack->push($this->httpAuthenticationMiddleware, self::MIDDLEWARE_HTTP_AUTH_KEY);
        $this->handlerStack->push($this->requestHeadersMiddleware, self::MIDDLEWARE_REQUEST_HEADERS_KEY);
        $this->enableRetryMiddleware();
        $this->handlerStack->push(Middleware::history($this->historyContainer), self::MIDDLEWARE_HISTORY_KEY);

        return new HttpClient([
            'curl' => $this->curlOptions,
            'verify' => false,
            'handler' => $this->handlerStack,
            'max_retries' => self::MAX_RETRIES,
            'cookies' => $this->cookieJar,
        ]);
    }

    public function disableRetryMiddleware()
    {
        $this->handlerStack->remove(self::MIDDLEWARE_RETRY_KEY);
    }

    public function enableRetryMiddleware()
    {
        $this->disableRetryMiddleware();
        $this->handlerStack->push(Middleware::retry($this->createRetryDecider()), self::MIDDLEWARE_RETRY_KEY);
    }

    /**
     * @return callable|null
     */
    protected function createInitialHandler()
    {
        return null;
    }

    /**
     * @return \Closure
     */
    private function createRetryDecider()
    {
        return function (
            $retries,
            RequestInterface $request,
            ResponseInterface $response = null,
            GuzzleException $exception = null
        ) {
            if (in_array($request->getMethod(), ['POST'])) {
                return false;
            }

            if ($retries >= self::MAX_RETRIES) {
                return false;
            }

            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response instanceof ResponseInterface && $response->getStatusCode() >= 500) {
                return true;
            }

            return false;
        };
    }
}
