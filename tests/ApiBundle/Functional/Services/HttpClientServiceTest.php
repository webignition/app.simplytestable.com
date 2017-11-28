<?php

namespace Tests\ApiBundle\Functional\Services;

use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Subscriber\Retry\RetrySubscriber as HttpRetrySubscriber;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class HttpClientServiceTest extends AbstractBaseTestCase
{
    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->httpClientService = $this->container->get(HttpClientService::class);
    }

    public function testSetUserAgent()
    {
        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(),
            HttpFixtureFactory::createSuccessResponse(),
            HttpFixtureFactory::createSuccessResponse(),
        ]);

        $httpClient = $this->httpClientService->get();

        $defaultUserAgent = $httpClient::getDefaultUserAgent();

        $httpClient->send($this->httpClientService->getRequest('http://example.com/'));

        $this->httpClientService->setUserAgent('foo');
        $httpClient->send($this->httpClientService->getRequest('http://example.com/'));

        $this->httpClientService->resetUserAgent();
        $httpClient->send($this->httpClientService->getRequest('http://example.com/'));

        $userAgentHeaderValues = [];

        foreach ($this->httpClientService->getHistory() as $requestIndex => $httpTransaction) {
            /* @var Request $sentRequest */
            $sentRequest = $httpTransaction['request'];

            $userAgentHeaderValues[] = $sentRequest->getHeader('user-agent');
        }

        $this->assertEquals(
            [
                $defaultUserAgent,
                'foo',
                $defaultUserAgent,
            ],
            $userAgentHeaderValues
        );
    }

    public function testEnableDisableRetrySubscriber()
    {
        $this->assertTrue(
            $this->doesHttpClientContainSubscriber('complete', HttpRetrySubscriber::class)
        );

        $this->httpClientService->disableRetrySubscriber();

        $this->assertFalse(
            $this->doesHttpClientContainSubscriber('complete', HttpRetrySubscriber::class)
        );
    }

    /**
     * @dataProvider createRequestDataProvider
     *
     * @param string $url
     * @param array $options
     * @param array $expectedConfig
     */
    public function testGetRequest($url, $options, $expectedConfig)
    {
        $request = $this->httpClientService->getRequest($url, $options);

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals($url, $request->getUrl());

        $this->assertEquals($expectedConfig, $request->getConfig()->toArray());
    }

    /**
     * @dataProvider createRequestDataProvider
     *
     * @param string $url
     * @param array $options
     * @param array $expectedConfig
     */
    public function testPostRequest($url, $options, $expectedConfig)
    {
        $request = $this->httpClientService->postRequest($url, $options);

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals($url, $request->getUrl());

        $this->assertEquals($expectedConfig, $request->getConfig()->toArray());
    }

    /**
     * @return array
     */
    public function createRequestDataProvider()
    {
        return [
            'default, no options' => [
                'url' => 'http://example.com/foo',
                'options' => [],
                'expectedConfig' => [
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ],
                    'redirect' => [
                        'max' => 5,
                        'strict' => false,
                        'referer' => false,
                        'protocols' => [
                            'http',
                            'https',
                        ],
                    ],
                    'decode_content' => true,
                    'verify' => false,
                ],
            ],
            'has options' => [
                'url' => 'http://example.com/foo',
                'options' => [
                    'allow_redirects' => [
                        'max' => 7,
                    ],
                    'verify' => true,
                ],
                'expectedConfig' => [
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ],
                    'redirect' => [
                        'max' => 7,
                        'strict' => false,
                        'referer' => false,
                        'protocols' => [
                            'http',
                            'https',
                        ],
                    ],
                    'decode_content' => true,
                    'verify' => true,
                ],
            ],
        ];
    }

    /**
     * @dataProvider setCookiesDataProvider
     *
     * @param array $cookies
     * @param string $expectedCookieHeaderValue
     */
    public function testSetCookies($cookies, $expectedCookieHeaderValue)
    {
        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(),
            HttpFixtureFactory::createSuccessResponse(),
        ]);

        $this->httpClientService->setCookies($cookies);

        $httpClient = $this->httpClientService->get();

        $httpClient->send($this->httpClientService->getRequest('http://example.com/'));

        $request = $this->httpClientService->getHistory()->getLastRequest();

        $this->assertEquals(
            $expectedCookieHeaderValue,
            $request->getHeader('cookie')
        );

        $this->httpClientService->clearCookies();

        $httpClient->send($this->httpClientService->getRequest('http://example.com/'));

        $request = $this->httpClientService->getHistory()->getLastRequest();

        $this->assertFalse($request->hasHeader('cookie'));
    }

    /**
     * @return array
     */
    public function setCookiesDataProvider()
    {
        return [
            'none' => [
                'cookies' => [],
                'expectedCookieHeaderValue' => ''
            ],
            'single cookie' => [
                'cookies' => [
                    [
                        'Name' => 'foo',
                        'Value' => 'bar',
                        'Domain' => '.example.com',
                    ],
                ],
                'expectedCookieHeaderValue' => 'foo=bar'
            ],
            'two cookies' => [
                'cookies' => [
                    [
                        'Name' => 'foo',
                        'Value' => 'bar',
                        'Domain' => '.example.com',
                    ],
                    [
                        'Name' => 'bar',
                        'Value' => 'foo',
                        'Domain' => '.example.com',
                    ],
                ],
                'expectedCookieHeaderValue' => 'foo=bar; bar=foo'
            ],
        ];
    }

    public function testSetBasicHttpAuthentication()
    {
        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(),
            HttpFixtureFactory::createSuccessResponse(),
            HttpFixtureFactory::createSuccessResponse(),
        ]);

        $this->httpClientService->setBasicHttpAuthorization('user', 'password');

        $httpClient = $this->httpClientService->get();

        $httpClient->send($this->httpClientService->getRequest('http://example.com/'));

        $request = $this->httpClientService->getHistory()->getLastRequest();

        $this->assertEquals(
            'Basic dXNlcjpwYXNzd29yZA==',
            $request->getHeader('authorization')
        );

        $this->httpClientService->setBasicHttpAuthorization(null, null);

        $httpClient->send($this->httpClientService->getRequest('http://example.com/'));

        $request = $this->httpClientService->getHistory()->getLastRequest();

        $this->assertEquals(
            'Basic dXNlcjpwYXNzd29yZA==',
            $request->getHeader('authorization')
        );

        $this->httpClientService->clearBasicHttpAuthorization();

        $httpClient->send($this->httpClientService->getRequest('http://example.com/'));

        $request = $this->httpClientService->getHistory()->getLastRequest();

        $this->assertFalse($request->hasHeader('authorization'));
    }

    /**
     * @param string $eventName
     * @param string $className
     *
     * @return bool
     */
    private function doesHttpClientContainSubscriber($eventName, $className)
    {
        $httpClient = $this->httpClientService->get();
        $completeListeners = $httpClient->getEmitter()->listeners($eventName);

        $hasSubscriber = false;

        foreach ($completeListeners as $listener) {
            $subscriber = $listener[0];

            if ($subscriber instanceof $className) {
                $hasSubscriber = true;
            }
        }

        return $hasSubscriber;
    }
}
