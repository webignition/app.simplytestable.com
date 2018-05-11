<?php

namespace SimplyTestable\ApiBundle\Services;

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

class FooHttpClientService
{
    const MIDDLEWARE_CACHE_KEY = 'cache';
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
     * @var HttpAuthenticationMiddleware
     */
    private $httpAuthenticationMiddleware;

    /**
     * @var CookieJarInterface
     */
    private $cookieJar;

    /**
     * @var HandlerStack
     */
    private $handlerStack;

    /**
     * @param array $curlOptions
     */
    public function __construct(array $curlOptions)
    {
        $this->setCurlOptions($curlOptions);
        $this->httpAuthenticationMiddleware = new HttpAuthenticationMiddleware();
        $this->cookieJar = new CookieJar();
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
     * Set cookies to be sent on all requests (dependent on cookie domain/secure matching rules)
     *
     * @param array $cookies
     */
    public function setCookies($cookies = [])
    {
        $this->clearCookies();

        if (empty($cookies)) {
            return;
        }

        foreach ($cookies as $cookie) {
            foreach ($cookie as $key => $value) {
                $cookie[ucfirst($key)] = $value;
            }

            $this->cookieJar->setCookie(new SetCookie($cookie));
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
        $this->enableRetryMiddleware();

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
