<?php

namespace Tests\ApiBundle\Unit\Model;

use SimplyTestable\ApiBundle\Model\Parameters;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationCredentials;

class ParametersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getCookiesDataProvider
     *
     * @param array $parametersArray
     * @param array $expectedCookieStrings
     */
    public function testGetCookies(array $parametersArray, array $expectedCookieStrings)
    {
        $parametersObject = new Parameters($parametersArray);

        $cookies = $parametersObject->getCookies();

        $this->assertEquals(count($expectedCookieStrings), count($cookies));

        foreach ($cookies as $cookieIndex => $cookie) {
            $this->assertEquals($expectedCookieStrings[$cookieIndex], (string)$cookie);
        }
    }

    /**
     * @return array
     */
    public function getCookiesDataProvider()
    {
        return [
            'no parameters' => [
                'parametersArray' => [],
                'expectedCookieStrings' => [],
            ],
            'empty cookies in parameters' => [
                'parametersArray' => [
                    'cookies' => [],
                ],
                'expectedCookieStrings' => [],
            ],
            'has cookies; lowercase keys' => [
                'parametersArray' => [
                    'cookies' => [
                        [
                            'name' => 'cookie-0',
                            'value' => 'value-0',
                            'domain' => 'foo',
                        ],
                        [
                            'name' => 'cookie-1',
                            'value' => 'value-1',
                            'domain' => 'foo',
                        ],
                    ],
                ],
                'expectedCookieStrings' => [
                    'cookie-0=value-0; Domain=foo; Path=/',
                    'cookie-1=value-1; Domain=foo; Path=/',
                ],
            ],
            'has cookies; uppercase keys' => [
                'parametersArray' => [
                    'cookies' => [
                        [
                            'NAME' => 'cookie-0',
                            'VALUE' => 'value-0',
                            'DOMAIN' => 'foo',
                        ],
                        [
                            'NAME' => 'cookie-1',
                            'VALUE' => 'value-1',
                            'DOMAIN' => 'foo',
                        ],
                    ],
                ],
                'expectedCookieStrings' => [
                    'cookie-0=value-0; Domain=foo; Path=/',
                    'cookie-1=value-1; Domain=foo; Path=/',
                ],
            ],
            'has cookies; ucfirst keys' => [
                'parametersArray' => [
                    'cookies' => [
                        [
                            'Name' => 'cookie-0',
                            'Value' => 'value-0',
                            'Domain' => 'foo',
                        ],
                        [
                            'Name' => 'cookie-1',
                            'Value' => 'value-1',
                            'Domain' => 'foo',
                        ],
                    ],
                ],
                'expectedCookieStrings' => [
                    'cookie-0=value-0; Domain=foo; Path=/',
                    'cookie-1=value-1; Domain=foo; Path=/',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getHttpAuthenticationCredentialsDataProvider
     *
     * @param string $url
     * @param array $parametersArray
     * @param array $expectedHttpAuthenticationCredentialsValues
     */
    public function testGetHttpAuthenticationCredentials(
        $url,
        array $parametersArray,
        array $expectedHttpAuthenticationCredentialsValues
    ) {
        $parametersObject = new Parameters($parametersArray);

        $httpAuthenticationCredentials = $parametersObject->getHttpAuthenticationCredentials($url);

        $this->assertInstanceOf(HttpAuthenticationCredentials::class, $httpAuthenticationCredentials);

        $this->assertEquals(
            $expectedHttpAuthenticationCredentialsValues['username'],
            $httpAuthenticationCredentials->getUsername()
        );

        $this->assertEquals(
            $expectedHttpAuthenticationCredentialsValues['password'],
            $httpAuthenticationCredentials->getPassword()
        );

        $this->assertEquals(
            $expectedHttpAuthenticationCredentialsValues['domain'],
            $httpAuthenticationCredentials->getDomain()
        );
    }

    /**
     * @return array
     */
    public function getHttpAuthenticationCredentialsDataProvider()
    {
        return [
            'no parameters' => [
                'url' => 'http://example.com/',
                'parametersArray' => [],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => '',
                    'password' => '',
                    'domain' => '',
                ],
            ],
            'http auth parameters; no username, has password' => [
                'url' => 'http://example.com/',
                'parametersArray' => [
                    'http-auth-password' => 'password value',
                ],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => '',
                    'password' => '',
                    'domain' => '',
                ],
            ],
            'http auth parameters; has username, no password' => [
                'url' => 'http://example.com/',
                'parametersArray' => [
                    'http-auth-username' => 'username value',
                ],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => 'username value',
                    'password' => '',
                    'domain' => 'example.com',
                ],
            ],
            'http auth parameters; has username, has password' => [
                'url' => 'http://example.com/',
                'parametersArray' => [
                    'http-auth-username' => 'username value',
                    'http-auth-password' => 'password value',
                ],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => 'username value',
                    'password' => 'password value',
                    'domain' => 'example.com',
                ],
            ],
            'http auth parameters; different domain' => [
                'url' => 'http://example.org/',
                'parametersArray' => [
                    'http-auth-username' => 'username value',
                    'http-auth-password' => 'password value',
                ],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => 'username value',
                    'password' => 'password value',
                    'domain' => 'example.org',
                ],
            ],
            'http auth parameters; different domain, subdomain' => [
                'url' => 'http://foo.example.org/',
                'parametersArray' => [
                    'http-auth-username' => 'username value',
                    'http-auth-password' => 'password value',
                ],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => 'username value',
                    'password' => 'password value',
                    'domain' => 'foo.example.org',
                ],
            ],
        ];
    }

    public function testGetAsArray()
    {
        $parametersArray = [
            'foo-key' => 1,
            'bar-key' => 2,
            'foobar-key' => 'foobar',
        ];

        $parameters = new Parameters($parametersArray);
        $this->assertEquals($parametersArray, $parameters->getAsArray());
    }

    public function testGet()
    {
        $parameters = new Parameters([
            'foo-key' => 'foo-value',
            'bar-key' => 'bar-value',
        ]);

        $this->assertEquals('foo-value', $parameters->get('foo-key'));
        $this->assertEquals('bar-value', $parameters->get('bar-key'));
        $this->assertNull($parameters->get('foobar-key'));
    }

    public function testSet()
    {
        $parameters = new Parameters();

        $this->assertNull($parameters->get('foo-key'));
        $this->assertNull($parameters->get('bar-key'));

        $parameters->set('foo-key', 'foo-value');
        $parameters->set('bar-key', 'bar-value');

        $this->assertEquals('foo-value', $parameters->get('foo-key'));
        $this->assertEquals('bar-value', $parameters->get('bar-key'));
        $this->assertNull($parameters->get('foobar-key'));
    }
}
