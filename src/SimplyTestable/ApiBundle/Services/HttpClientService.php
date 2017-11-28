<?php
namespace SimplyTestable\ApiBundle\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Message\RequestInterface;
//use Guzzle\Plugin\Backoff\BackoffPlugin;
//use Doctrine\Common\Cache\MemcacheCache;
//use Guzzle\Cache\DoctrineCacheAdapter;
//use Guzzle\Plugin\Cache\CachePlugin;
//use Guzzle\Plugin\History\HistoryPlugin;
//use Symfony\Component\HttpFoundation\ParameterBag;
//use webignition\Cookie\UrlMatcher\UrlMatcher;
use GuzzleHttp\Subscriber\Cookie as HttpCookieSubscriber;
use GuzzleHttp\Subscriber\Retry\RetrySubscriber as HttpRetrySubscriber;
use GuzzleHttp\Subscriber\History as HttpHistorySubscriber;
use Symfony\Component\HttpFoundation\ParameterBag;

class HttpClientService
{
    const PARAMETER_KEY_COOKIES = 'cookies';
    const PARAMETER_KEY_HTTP_AUTH_USERNAME = 'http-auth-username';
    const PARAMETER_KEY_HTTP_AUTH_PASSWORD = 'http-auth-password';

    /**
     * @var HttpClient
     */
    private $httpClient = null;

    /**
     * @var array
     */
    private $curlOptions = array();

    /**
     * @var HttpHistorySubscriber
     */
    private $historySubscriber;

    /**
     * @var HttpCookieSubscriber
     */
    private $cookieSubscriber;

    /**
     * @var HttpRetrySubscriber
     */
    private $retrySubscriber;

    /**
     * @param array $curlOptions
     */
    public function __construct($curlOptions)
    {
        foreach ($curlOptions as $curlOption) {
            if (defined($curlOption['name'])) {
                $this->curlOptions[constant($curlOption['name'])] = $curlOption['value'];
            }
        }

        $this->historySubscriber = new HttpHistorySubscriber();
        $this->cookieSubscriber = new HttpCookieSubscriber();
        $this->retrySubscriber = $this->createRetrySubscriber();

        $this->httpClient = new HttpClient([
            'config' => [
                'curl' => $this->curlOptions
            ],
            'defaults' => [
                'verify' => false,
            ],
        ]);

        $this->enableRetrySubscriber();
        $this->httpClient->getEmitter()->attach($this->historySubscriber);
        $this->httpClient->getEmitter()->attach($this->cookieSubscriber);
    }

    public function enableRetrySubscriber()
    {
        $this->httpClient->getEmitter()->attach($this->retrySubscriber);
    }

    public function disableRetrySubscriber()
    {
        $this->httpClient->getEmitter()->detach($this->retrySubscriber);
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $defaultHeaders = $this->get()->getDefaultOption('headers');
        $defaultHeaders['User-Agent'] = $userAgent;

        $this->get()->setDefaultOption('headers', $defaultHeaders);
    }

    public function resetUserAgent()
    {
        $client = $this->get();
        $this->setUserAgent($client::getDefaultUserAgent());
    }

    /**
     * @return HttpRetrySubscriber
     */
    protected function createRetrySubscriber()
    {
        $filter = HttpRetrySubscriber::createChainFilter([
            // Does early filter to force non-idempotent methods to NOT be retried.
            HttpRetrySubscriber::createIdempotentFilter(),
            // Retry curl-level errors
            HttpRetrySubscriber::createCurlFilter(),
            // Performs the last check, returning ``true`` or ``false`` based on
            // if the response received a 500 or 503 status code.
            HttpRetrySubscriber::createStatusFilter([500, 503])
        ]);

        return new HttpRetrySubscriber(['filter' => $filter]);
    }

    /**
     * @return HttpClient
     */
    public function get()
    {
        return $this->httpClient;
    }

    /**
     * @param string $url
     * @param array $options
     *
     * @return RequestInterface
     */
    public function getRequest($url, array $options = [])
    {
        return $this->createRequest('GET', $url, $options);
    }


    /**
     * @param string $url
     * @param array $options
     *
     * @return RequestInterface
     */
    public function postRequest($url, array $options = [])
    {
        return $this->createRequest('POST', $url, $options);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $options
     *
     * @return RequestInterface
     */
    private function createRequest($method, $url, $options)
    {
        $options['config'] = [
            'curl' => $this->curlOptions
        ];

        return $this->get()->createRequest(
            $method,
            $url,
            $options
        );
    }

    /**
     * @return HttpHistorySubscriber
     */
    public function getHistory()
    {
        return $this->historySubscriber;
    }

    /**
     * Set cookies to be sent on all requests (dependent on cookie domain/secure matching rules)
     *
     * @param array $cookies
     */
    public function setCookies($cookies = [])
    {
        $this->cookieSubscriber->getCookieJar()->clear();
        if (!empty($cookies)) {
            foreach ($cookies as $cookie) {
                foreach ($cookie as $key => $value) {
                    $cookie[ucfirst($key)] = $value;
                }

                $this->cookieSubscriber->getCookieJar()->setCookie(new SetCookie($cookie));
            }
        }
    }

    public function clearCookies()
    {
        $this->cookieSubscriber->getCookieJar()->clear();
    }

    public function setBasicHttpAuthorization($username, $password)
    {
        if (empty($username) && empty($password)) {
            return;
        }

        $this->get()->setDefaultOption(
            'auth',
            [$username, $password]
        );
    }

    public function clearBasicHttpAuthorization()
    {
        $this->get()->setDefaultOption(
            'auth',
            null
        );
    }

    /**
     * @param array $parameters
     */
    public function setCookiesFromParameters($parameters)
    {
        $this->clearCookies();

        $parameterBag = new ParameterBag($parameters);

        if ($parameterBag->has(self::PARAMETER_KEY_COOKIES)) {
            $this->setCookies($parameterBag->get(self::PARAMETER_KEY_COOKIES));
        }
    }

    /**
     * @param array $parameters
     */
    public function setBasicHttpAuthenticationFromParameters($parameters)
    {
        $this->clearBasicHttpAuthorization();

        $parameterBag = new ParameterBag($parameters);

        $hasHttpAuthUserNameParameter = $parameterBag->has(self::PARAMETER_KEY_HTTP_AUTH_USERNAME);
        $hasHttpAuthPasswordParameter = $parameterBag->has(self::PARAMETER_KEY_HTTP_AUTH_PASSWORD);

        if ($hasHttpAuthUserNameParameter || $hasHttpAuthPasswordParameter) {
            $this->setBasicHttpAuthorization(
                $parameterBag->get(self::PARAMETER_KEY_HTTP_AUTH_USERNAME),
                $parameterBag->get(self::PARAMETER_KEY_HTTP_AUTH_PASSWORD)
            );
        }
    }


//    /**
//     * @param Request $request
//     * @param array $parameters
//     */
//    public function prepareRequest(Request $request, $parameters = [])
//    {
//        $parameterBag = new ParameterBag($parameters);
//
//        $this->setRequestAuthentication($request, $parameterBag);
//        $this->setRequestCookies($request, $parameterBag);
//    }
//
//
//    /**
//     * @param Request $request
//     * @param ParameterBag $parameters
//     */
//    private function setRequestAuthentication(Request $request, ParameterBag $parameters)
//    {
//        $hasHttpAuthUserNameParameter = $parameters->has(self::PARAMETER_KEY_HTTP_AUTH_USERNAME);
//        $hasHttpAuthPasswordParameter = $parameters->has(self::PARAMETER_KEY_HTTP_AUTH_PASSWORD);
//
//        if ($hasHttpAuthUserNameParameter || $hasHttpAuthPasswordParameter) {
//            $request->setAuth(
//                ($hasHttpAuthUserNameParameter) ? $parameters->get(self::PARAMETER_KEY_HTTP_AUTH_USERNAME) : '',
//                ($hasHttpAuthPasswordParameter) ? $parameters->get(self::PARAMETER_KEY_HTTP_AUTH_PASSWORD) : '',
//                'any'
//            );
//        }
//    }
//
//    /**
//     * @param Request $request
//     * @param ParameterBag $parameters
//     */
//    private function setRequestCookies(Request $request, ParameterBag $parameters)
//    {
//        if (!is_null($request->getCookies())) {
//            foreach ($request->getCookies() as $name => $value) {
//                $request->removeCookie($name);
//            }
//        }
//
//        if ($parameters->has(self::PARAMETER_KEY_COOKIES)) {
//            $cookieUrlMatcher = new UrlMatcher();
//
//            foreach ($parameters->get(self::PARAMETER_KEY_COOKIES) as $cookie) {
//                if ($cookieUrlMatcher->isMatch($cookie, $request->getUrl())) {
//                    $request->addCookie($cookie['name'], $cookie['value']);
//                }
//            }
//        }
//    }
}
