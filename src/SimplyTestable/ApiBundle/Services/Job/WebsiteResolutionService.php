<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\ConnectException;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Exception\Services\Job\WebsiteResolutionException;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use webignition\Url\Url;
use webignition\Url\Resolver\Resolver as UrlResolver;
use webignition\GuzzleHttp\Exception\CurlException\Factory as GuzzleCurlExceptionFactory;

class WebsiteResolutionService
{
    const URL_RESOLVER_USER_AGENT = 'ST url resolver (http://bit.ly/RlhKCL)';
    const CURL_TIMEOUT_MS = 10000;

    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @var WebSiteService
     */
    private $websiteService;

    /**
     * @var UrlResolver
     */
    private $urlResolver = null;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param JobService $jobService
     * @param HttpClientService $httpClientService
     * @param WebSiteService $websiteService
     * @param UrlResolver $urlResolver
     * @param StateService $stateService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        JobService $jobService,
        HttpClientService $httpClientService,
        WebSiteService $websiteService,
        UrlResolver $urlResolver,
        StateService $stateService,
        EntityManagerInterface $entityManager
    ) {
        $this->jobService = $jobService;
        $this->httpClientService = $httpClientService;
        $this->websiteService = $websiteService;
        $this->urlResolver = $urlResolver;
        $this->stateService = $stateService;
        $this->entityManager = $entityManager;
    }

    /**
     * @param Job $job
     * @throws WebsiteResolutionException
     */
    public function resolve(Job $job)
    {
        $jobIsNew = JobService::STARTING_STATE === $job->getState()->getName();

        if (!$jobIsNew) {
            throw new WebsiteResolutionException(
                'Job is in wrong state, currently "'.$job->getState()->getName().'"',
                WebsiteResolutionException::CODE_JOB_IN_WRONG_STATE_CODE
            );
        }

        $jobResolvingState = $this->stateService->get(JobService::RESOLVING_STATE);
        $jobResolvedState = $this->stateService->get(JobService::RESOLVED_STATE);

        $job->setState($jobResolvingState);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $jobParameters = $job->getParametersArray();

        $this->httpClientService->setUserAgent(self::URL_RESOLVER_USER_AGENT);
        $this->httpClientService->setCookiesFromParameters($jobParameters);
        $this->httpClientService->setBasicHttpAuthenticationFromParameters($jobParameters);

        try {
            $jobUrl = $job->getWebsite()->getCanonicalUrl();
            $resolvedUrl = $this->urlResolver->resolve($jobUrl);

            if ($job->getType()->getName() == 'Full site') {
                $resolvedUrl = $this->trimToRootUrl($resolvedUrl);
            }

            if ($jobUrl != $resolvedUrl) {
                $job->setWebsite($this->websiteService->get($resolvedUrl));
            }

            $job->setState($jobResolvedState);
        } catch (ConnectException $connectException) {
            $curlException = GuzzleCurlExceptionFactory::fromConnectException($connectException);

            $this->jobService->reject($job, 'curl-' . $curlException->getCurlCode());
        }

        $this->httpClientService->resetUserAgent();
        $this->httpClientService->clearCookies();
        $this->httpClientService->clearBasicHttpAuthorization();

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function trimToRootUrl($url)
    {
        $urlObject = new Url($url);

        return $urlObject->getScheme() . '://' . $urlObject->getHost() . '/';
    }
}