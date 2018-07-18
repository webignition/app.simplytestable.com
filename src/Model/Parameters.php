<?php

namespace App\Model;

use GuzzleHttp\Cookie\SetCookie;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationCredentials;
use webignition\NormalisedUrl\NormalisedUrl;

class Parameters implements \JsonSerializable
{
    const PARAMETER_KEY_COOKIES = 'cookies';
    const PARAMETER_HTTP_AUTH_USERNAME = 'http-auth-username';
    const PARAMETER_HTTP_AUTH_PASSWORD = 'http-auth-password';

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * @return SetCookie[]
     */
    public function getCookies()
    {
        $cookies = [];

        if (!$this->has(self::PARAMETER_KEY_COOKIES)) {
            return $cookies;
        }

        $cookieValuesCollection = $this->parameters[self::PARAMETER_KEY_COOKIES];

        foreach ($cookieValuesCollection as $cookieValues) {
            foreach ($cookieValues as $key => $value) {
                $normalisedKey = ucfirst(strtolower($key));

                unset($cookieValues[$key]);
                $cookieValues[$normalisedKey] = $value;
            }

            $cookies[] = new SetCookie($cookieValues);
        }

        return $cookies;
    }

    /**
     * @param string $url
     *
     * @return HttpAuthenticationCredentials
     */
    public function getHttpAuthenticationCredentials($url)
    {
        if (!$this->has(self::PARAMETER_HTTP_AUTH_USERNAME)) {
            return new HttpAuthenticationCredentials();
        }

        $username = $this->parameters[self::PARAMETER_HTTP_AUTH_USERNAME];
        $password = $this->has(self::PARAMETER_HTTP_AUTH_PASSWORD)
            ? $this->parameters[self::PARAMETER_HTTP_AUTH_PASSWORD]
            : null;

        $urlObject = new NormalisedUrl($url);

        return new HttpAuthenticationCredentials($username, $password, $urlObject->getHost());
    }

    /**
     * @return array
     */
    public function getAsArray()
    {
        return $this->parameters;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function get($key)
    {
        return array_key_exists($key, $this->parameters) ? $this->parameters[$key] : null;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    /**
     * @param Parameters $parameters
     */
    public function merge(Parameters $parameters)
    {
        $this->parameters = array_merge(
            $this->parameters,
            $parameters->getAsArray()
        );
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    private function has($key)
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return empty($this->parameters) ? '' : json_encode($this);
    }
}
