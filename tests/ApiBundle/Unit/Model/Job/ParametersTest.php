<?php

namespace Tests\ApiBundle\Unit\Model\Job;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Model\Job\Parameters;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationCredentials;

class ParametersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getCookiesDataProvider
     *
     * @param array $jobParametersArray
     * @param array $expectedCookieStrings
     */
    public function testGetCookies(array $jobParametersArray, array $expectedCookieStrings)
    {
        $website = new WebSite();
        $website->setCanonicalUrl('http://example.com/');

        $job = new Job();
        $job->setParameters(json_encode($jobParametersArray));
        $job->setWebsite($website);

        $parametersObject = new Parameters($job);

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
                'jobParametersArray' => [],
                'expectedCookieStrings' => [],
            ],
            'empty cookies in parameters' => [
                'jobParametersArray' => [
                    'cookies' => [],
                ],
                'expectedCookieStrings' => [],
            ],
            'has cookies; lowercase keys' => [
                'jobParametersArray' => [
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
                'jobParametersArray' => [
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
                'jobParametersArray' => [
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
     * @param string $jobUrl
     * @param array $jobParametersArray
     * @param array $expectedHttpAuthenticationCredentialsValues
     */
    public function testGetHttpAuthenticationCredentials(
        $jobUrl,
        array $jobParametersArray,
        array $expectedHttpAuthenticationCredentialsValues
    ) {
        $website = new WebSite();
        $website->setCanonicalUrl($jobUrl);

        $job = new Job();
        $job->setParameters(json_encode($jobParametersArray));
        $job->setWebsite($website);

        $parametersObject = new Parameters($job);

        $httpAuthenticationCredentials = $parametersObject->getHttpAuthenticationCredentials();

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
                'jobUrl' => 'http://example.com/',
                'jobParametersArray' => [],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => '',
                    'password' => '',
                    'domain' => '',
                ],
            ],
            'http auth parameters; no username, has password' => [
                'jobUrl' => 'http://example.com/',
                'jobParametersArray' => [
                    'http-auth-password' => 'password value',
                ],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => '',
                    'password' => '',
                    'domain' => '',
                ],
            ],
            'http auth parameters; has username, no password' => [
                'jobUrl' => 'http://example.com/',
                'jobParametersArray' => [
                    'http-auth-username' => 'username value',
                ],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => 'username value',
                    'password' => '',
                    'domain' => 'example.com',
                ],
            ],
            'http auth parameters; has username, has password' => [
                'jobUrl' => 'http://example.com/',
                'jobParametersArray' => [
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
                'jobUrl' => 'http://example.org/',
                'jobParametersArray' => [
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
                'jobUrl' => 'http://foo.example.org/',
                'jobParametersArray' => [
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
}
