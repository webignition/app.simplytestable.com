<?php
namespace SimplyTestable\ApiBundle\Services;

use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\Message\Request;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Doctrine\Common\Cache\MemcacheCache;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\History\HistoryPlugin;
use Symfony\Component\HttpFoundation\ParameterBag;
use webignition\Cookie\UrlMatcher\UrlMatcher;

class HttpClientService
{
    const PARAMETER_KEY_COOKIES = 'cookies';
    const PARAMETER_KEY_HTTP_AUTH_USERNAME = 'http-auth-username';
    const PARAMETER_KEY_HTTP_AUTH_PASSWORD = 'http-auth-password';

    /**
     * @var \Guzzle\Http\Client
     */
    protected $httpClient = null;

    /**
     * @var array
     */
    private $curlOptions = array();

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
    }

    /**
     * @return HttpClient
     */
    public function get()
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = new HttpClient();

            $this->httpClient->addSubscriber(BackoffPlugin::getExponentialBackoff(
                3,
                array(500, 503, 504)
            ));

            $this->httpClient->addSubscriber(new HistoryPlugin());
        }

        return $this->httpClient;
    }

    /**
     * @param string $uri
     * @param array $headers
     * @param string $body
     *
     * @return Request
     */
    public function getRequest($uri = null, $headers = null, $body = null)
    {
        $request = $this->get()->get($uri, $headers, $body);
        $request->setHeader('Accept-Encoding', 'gzip,deflate');

        foreach ($this->curlOptions as $key => $value) {
            $request->getCurlOptions()->set($key, $value);
        }

        return $request;
    }

    /**
     * @param string $uri
     * @param array $headers
     * @param array $postBody
     *
     * @return Request
     */
    public function postRequest($uri = null, $headers = null, $postBody = null)
    {
        $request = $this->get()->post($uri, $headers, $postBody);

        foreach ($this->curlOptions as $key => $value) {
            $request->getCurlOptions()->set($key, $value);
        }

        return $request;
    }


    /**
     * @param Request $request
     * @param array $parameters
     */
    public function prepareRequest(Request $request, $parameters = [])
    {
        $parameterBag = new ParameterBag($parameters);

        $this->setRequestAuthentication($request, $parameterBag);
        $this->setRequestCookies($request, $parameterBag);
    }


    /**
     * @param Request $request
     * @param ParameterBag $parameters
     */
    private function setRequestAuthentication(Request $request, ParameterBag $parameters)
    {
        $hasHttpAuthUserNameParameter = $parameters->has(self::PARAMETER_KEY_HTTP_AUTH_USERNAME);
        $hasHttpAuthPasswordParameter = $parameters->has(self::PARAMETER_KEY_HTTP_AUTH_PASSWORD);

        if ($hasHttpAuthUserNameParameter || $hasHttpAuthPasswordParameter) {
            $request->setAuth(
                ($hasHttpAuthUserNameParameter) ? $parameters->get(self::PARAMETER_KEY_HTTP_AUTH_USERNAME) : '',
                ($hasHttpAuthPasswordParameter) ? $parameters->get(self::PARAMETER_KEY_HTTP_AUTH_PASSWORD) : '',
                'any'
            );
        }
    }

    /**
     * @param Request $request
     * @param ParameterBag $parameters
     */
    private function setRequestCookies(Request $request, ParameterBag $parameters)
    {
        if (!is_null($request->getCookies())) {
            foreach ($request->getCookies() as $name => $value) {
                $request->removeCookie($name);
            }
        }

        if ($parameters->has(self::PARAMETER_KEY_COOKIES)) {
            $cookieUrlMatcher = new UrlMatcher();

            foreach ($parameters->get(self::PARAMETER_KEY_COOKIES) as $cookie) {
                if ($cookieUrlMatcher->isMatch($cookie, $request->getUrl())) {
                    $request->addCookie($cookie['name'], $cookie['value']);
                }
            }
        }
    }
}
