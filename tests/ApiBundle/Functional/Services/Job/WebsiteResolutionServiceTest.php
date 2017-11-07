<?php

namespace Tests\ApiBundle\Functional\Services\Job;

use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Message\Request;
use SimplyTestable\ApiBundle\Entity\Job\RejectionReason;
use SimplyTestable\ApiBundle\Exception\Services\Job\WebsiteResolutionException;
use SimplyTestable\ApiBundle\Services\Job\WebsiteResolutionService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\StateFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->websiteResolutionService = $this->container->get('simplytestable.services.jobwebsiteresolutionservice');
        $this->jobFactory = new JobFactory($this->container);
    }

    public function testResolveJobInWrongState()
    {
        $this->expectException(WebsiteResolutionException::class);
        $this->expectExceptionMessage('Job is in wrong state, currently "foo"');
        $this->expectExceptionCode(WebsiteResolutionException::CODE_JOB_IN_WRONG_STATE_CODE);

        $job = $this->jobFactory->create();
        $job->setState(StateFactory::create('foo'));

        $this->websiteResolutionService->resolve($job);
    }

    /**
     * @dataProvider resolveRejectionDueToCurlExceptionDataProvider
     *
     * @param int $curlCode
     * @param string $expectedRejectionReason
     */
    public function testResolveRejectionDueToCurlException($curlCode, $expectedRejectionReason)
    {
        $jobRejectionReasonRepository = $this->container->get('simplytestable.repository.jobrejectionreason');

        $curlException = new CurlException();
        $curlException->setError('', $curlCode);

        $this->queueHttpFixtures([
            $curlException,
        ]);

        $siteRootUrl = 'http://foo.example.com/';

        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $siteRootUrl,
        ]);

        $this->websiteResolutionService->resolve($job);

        $jobRejectionReason = $jobRejectionReasonRepository->findOneBy([
            'job' => $job,
        ]);

        $this->assertEquals(JobService::REJECTED_STATE, $job->getState()->getName());
        $this->assertInstanceOf(RejectionReason::class, $jobRejectionReason);
        $this->assertEquals($expectedRejectionReason, $jobRejectionReason->getReason());
        $this->assertEquals($siteRootUrl, $job->getWebsite()->getCanonicalUrl());
    }

    /**
     * @return array
     */
    public function resolveRejectionDueToCurlExceptionDataProvider()
    {
        return [
            'curl 6' => [
                'curlCode' => 6,
                'expectedRejectionReason' => 'curl-6',
            ],
            'curl 28' => [
                'curlCode' => 28,
                'expectedRejectionReason' => 'curl-28',
            ],
        ];
    }

    /**
     * @dataProvider resolveDataProvider
     *
     * @param array $jobValues
     * @param array $httpFixtures
     * @param string $expectedResolvedUrl
     */
    public function testResolve($jobValues, $httpFixtures, $expectedResolvedUrl)
    {
        $this->queueHttpFixtures($httpFixtures);
        $httpClientService = $this->container->get('simplytestable.services.httpclientservice');

        $job = $this->jobFactory->create($jobValues);

        $this->websiteResolutionService->resolve($job);

        $this->assertEquals(JobService::RESOLVED_STATE, $job->getState()->getName());
        $this->assertEquals($expectedResolvedUrl, $job->getWebsite()->getCanonicalUrl());

        $jobParameters = $job->getParametersArray();

        $httpHistory = $httpClientService->getHistoryPlugin()->getAll();

        foreach ($httpHistory as $httpTransaction) {
            /* @var Request $request */
            $request = $httpTransaction['request'];

            if (isset($jobParameters['cookies'])) {
                $this->assertEquals([
                    $this->cookie['name'] => $this->cookie['value'],
                ], $request->getCookies());
            }

            if (isset($jobParameters['http-auth-username'])) {
                $this->assertEquals(self::HTTP_AUTH_USERNAME, $request->getUsername());
                $this->assertEquals(self::HTTP_AUTH_PASSWORD, $request->getPassword());
            }
        }
    }

    /**
     * @return array
     */
    public function resolveDataProvider()
    {
        return [
            JobTypeService::FULL_SITE_NAME => [
                'jobValues' => [
                    JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                    JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                ],
                'httpFixtures' => [
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://foo.example.com/',
            ],
            'single url with cookies and http auth' => [
                'jobValues' => [
                    JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
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
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://foo.example.com/bar',
            ],
        ];
    }
}
