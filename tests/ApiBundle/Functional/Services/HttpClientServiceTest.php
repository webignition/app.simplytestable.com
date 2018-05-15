<?php

namespace Tests\ApiBundle\Functional\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Tests\ApiBundle\Services\TestHttpClientService;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationCredentials;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class HttpClientServiceTest extends AbstractBaseTestCase
{
    /**
     * @var TestHttpClientService
     */
    private $httpClientService;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->httpClientService = $this->container->get(HttpClientService::class);
        $this->httpClient = $this->container->get(HttpClient::class);
    }

    public function testGetHttpClient()
    {
        $this->assertEquals(
            spl_object_hash($this->httpClient),
            spl_object_hash($this->httpClientService->getHttpClient())
        );
    }

    public function testGetHistory()
    {
        $this->assertInstanceOf(HttpHistoryContainer::class, $this->httpClientService->getHistory());
    }

    public function testSetCookiesClearCookies()
    {
        $successResponse = new Response();

        $this->httpClientService->appendFixtures([
            $successResponse,
            $successResponse,
            $successResponse,
        ]);

        $request = new Request('GET', 'http://example.com/');

        $this->httpClient->send($request);
        $this->httpClientService->setCookies([
            new SetCookie([
                'Name' => 'cookie-0-name',
                'Value' => 'cookie-0-value',
                'Domain' => 'example.com',
            ]),
            new SetCookie([
                'Name' => 'cookie-1-name',
                'Value' => 'cookie-1-value',
                'Domain' => 'example.com',
            ])
        ]);
        $this->httpClient->send($request);

        $this->httpClientService->clearCookies();
        $this->httpClient->send($request);

        $history = $this->httpClientService->getHistory();
        $requests = $history->getRequests();

        $this->assertEquals('', $requests[0]->getHeaderLine('cookie'));
        $this->assertEquals(
            'cookie-0-name=cookie-0-value; cookie-1-name=cookie-1-value',
            $requests[1]->getHeaderLine('cookie')
        );
        $this->assertEquals('', $requests[2]->getHeaderLine('cookie'));
    }

    public function testSetBasicHttpAuthorizationClearBasicHttpAuthorization()
    {
        $successResponse = new Response();

        $this->httpClientService->appendFixtures([
            $successResponse,
            $successResponse,
            $successResponse,
        ]);

        $request = new Request('GET', 'http://example.com/');

        $this->httpClient->send($request);
        $this->httpClientService->setBasicHttpAuthorization(new HttpAuthenticationCredentials(
            'username',
            'password',
            'example.com'
        ));
        $this->httpClient->send($request);

        $this->httpClientService->clearBasicHttpAuthorization();
        $this->httpClient->send($request);

        $history = $this->httpClientService->getHistory();
        $requests = $history->getRequests();

        $this->assertEquals('', $requests[0]->getHeaderLine('authorization'));
        $this->assertEquals('Basic dXNlcm5hbWU6cGFzc3dvcmQ=', $requests[1]->getHeaderLine('authorization'));
        $this->assertEquals('', $requests[2]->getHeaderLine('authorization'));
    }

    public function testSetRequestHeader()
    {
        $successResponse = new Response();

        $this->httpClientService->appendFixtures([
            $successResponse,
            $successResponse,
        ]);

        $request = new Request('GET', 'http://example.com/');
        $this->httpClient->send($request);

        $this->httpClientService->setRequestHeader('X-Foo', 'Foo');
        $this->httpClient->send($request);

        $history = $this->httpClientService->getHistory();
        $requests = $history->getRequests();

        $this->assertEquals('', $requests[0]->getHeaderLine('x-foo'));
        $this->assertEquals('Foo', $requests[1]->getHeaderLine('x-foo'));
    }
}
