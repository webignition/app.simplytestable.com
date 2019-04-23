<?php

namespace App\Tests\Functional\Services\Job;

use App\Entity\State;
use App\Tests\Services\JobFactory;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use App\Entity\Job\Job;
use App\Entity\Job\RejectionReason;
use App\Exception\Services\Job\WebsiteResolutionException;
use App\Services\HttpClientService;
use App\Services\Job\WebsiteResolutionService;
use App\Services\JobTypeService;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\TestHttpClientService;

class WebsiteResolutionServiceTest extends AbstractBaseTestCase
{
    const HTTP_AUTH_USERNAME = 'http-user';
    const HTTP_AUTH_PASSWORD = 'http-pass';

    /**
     * @var array
     */
    private $cookie = [
        'domain' => '.example.com',
        'name' => 'cookie-name',
        'value' => 'cookie-value',
    ];

    /**
     * @var WebsiteResolutionService
     */
    private $websiteResolutionService;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var TestHttpClientService
     */
    private $httpClientService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->websiteResolutionService = self::$container->get(WebsiteResolutionService::class);
        $this->jobFactory = self::$container->get(JobFactory::class);
        $this->httpClientService = self::$container->get(HttpClientService::class);
    }

    public function testResolveJobInWrongState()
    {
        $this->expectException(WebsiteResolutionException::class);
        $this->expectExceptionMessage('Job is in wrong state, currently "foo"');
        $this->expectExceptionCode(WebsiteResolutionException::CODE_JOB_IN_WRONG_STATE_CODE);

        $job = $this->jobFactory->create();
        $job->setState(State::create('foo'));

        $this->websiteResolutionService->resolve($job);
    }

    /**
     * @dataProvider resolveRejectionDueToCurlExceptionDataProvider
     *
     * @param array $httpFixtures
     * @param string $expectedRejectionReason
     */
    public function testResolveRejectionDueToCurlException(array $httpFixtures, $expectedRejectionReason)
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $jobRejectionReasonRepository = $entityManager->getRepository(RejectionReason::class);

        $this->httpClientService->appendFixtures($httpFixtures);

        $siteRootUrl = 'http://foo.example.com/';

        $job = $this->jobFactory->create([
            JobFactory::KEY_URL => $siteRootUrl,
        ]);

        $this->websiteResolutionService->resolve($job);

        $jobRejectionReason = $jobRejectionReasonRepository->findOneBy([
            'job' => $job,
        ]);

        $this->assertEquals(Job::STATE_REJECTED, $job->getState()->getName());
        $this->assertInstanceOf(RejectionReason::class, $jobRejectionReason);
        $this->assertEquals($expectedRejectionReason, $jobRejectionReason->getReason());
        $this->assertEquals($siteRootUrl, $job->getWebsite()->getCanonicalUrl());
    }

    /**
     * @return array
     */
    public function resolveRejectionDueToCurlExceptionDataProvider()
    {
        $curl6ConnectException = ConnectExceptionFactory::create(6, 'DNS failure');
        $curl28ConnectException = ConnectExceptionFactory::create(28, 'Operation timed out');

        return [
            'curl 6' => [
                'httpFixtures' => array_fill(0, 6, $curl6ConnectException),
                'expectedRejectionReason' => 'curl-6',
            ],
            'curl 28' => [
                'httpFixtures' => array_fill(0, 6, $curl28ConnectException),
                'expectedRejectionReason' => 'curl-28',
            ],
            'curl 0' => [
                'httpFixtures' => [
                    new RequestException(
                        'cURL error 0: The cURL request was retried 3 times and did not succeed.',
                        \Mockery::mock(RequestInterface::class)
                    )
                ],
                'expectedRejectionReason' => 'curl-0',
            ],
            'curl 1' => [
                'httpFixtures' => [
                    new RequestException(
                        'cURL error 1: Protocol thttp not supported or disabled in libcurl',
                        \Mockery::mock(RequestInterface::class)
                    )
                ],
                'expectedRejectionReason' => 'curl-1',
            ],
        ];
    }

    /**
     * @dataProvider resolveSuccessDataProvider
     *
     * @param array $jobValues
     * @param array $httpFixtures
     * @param string $expectedResolvedUrl
     * @param array $expectedRequestPropertiesCollection
     */
    public function testResolveSuccess(
        $jobValues,
        $httpFixtures,
        $expectedResolvedUrl,
        $expectedRequestPropertiesCollection
    ) {
        $this->httpClientService->appendFixtures($httpFixtures);

        $job = $this->jobFactory->create($jobValues);

        $this->websiteResolutionService->resolve($job);

        $this->assertEquals(Job::STATE_RESOLVED, $job->getState()->getName());
        $this->assertEquals($expectedResolvedUrl, $job->getWebsite()->getCanonicalUrl());

        $requestPropertiesCollection = [];

        foreach ($this->httpClientService->getHistory() as $httpTransaction) {
            /* @var RequestInterface $request */
            $request = $httpTransaction['request'];

            $requestProperties = [];

            foreach (['user-agent', 'cookie', 'authorization'] as $headerKey) {
                $requestProperties[$headerKey] = $request->getHeaderLine($headerKey);
            }

            $requestPropertiesCollection[] = $requestProperties;
        }

        $this->assertEquals($expectedRequestPropertiesCollection, $requestPropertiesCollection);
    }

    /**
     * @return array
     */
    public function resolveSuccessDataProvider()
    {
        return [
            'Full site; redirects' => [
                'jobValues' => [
                    JobFactory::KEY_URL => 'http://example.com/',
                    JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                ],
                'httpFixtures' => [
                    new Response(301, ['location' => 'http://foo.example.com/bar']),
                    new Response(),
                ],
                'expectedResolvedUrl' => 'http://foo.example.com/',
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => WebsiteResolutionService::URL_RESOLVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => WebsiteResolutionService::URL_RESOLVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                ],
            ],
            'single url with cookies and http auth' => [
                'jobValues' => [
                    JobFactory::KEY_URL => 'http://example.com/',
                    JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                    JobFactory::KEY_PARAMETERS => [
                        'cookies' => [
                            $this->cookie,
                        ],
                        'http-auth-username' => self::HTTP_AUTH_USERNAME,
                        'http-auth-password' => self::HTTP_AUTH_PASSWORD,
                    ],
                ],
                'httpFixtures' => [
                    new Response(),
                ],
                'expectedResolvedUrl' => 'http://example.com/',
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => WebsiteResolutionService::URL_RESOLVER_USER_AGENT,
                        'cookie' => 'cookie-name=cookie-value',
                        'authorization' => 'Basic aHR0cC11c2VyOmh0dHAtcGFzcw==',
                    ],
                ],
            ],
        ];
    }
}
