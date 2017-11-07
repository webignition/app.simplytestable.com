<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use Doctrine\ORM\EntityManagerInterface;
use Guzzle\Http\Exception\CurlException;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Exception\Services\Job\WebsiteResolutionException;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\UrlResolver;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use webignition\Url\Url;

class WebsiteResolutionService
{
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

        $jobResolvingState = $this->stateService->fetch(JobService::RESOLVING_STATE);
        $jobResolvedState = $this->stateService->fetch(JobService::RESOLVED_STATE);

        $job->setState($jobResolvingState);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->urlResolver->configureForJob($job);

        try {
            $resolvedUrl = $this->urlResolver->resolve($job->getWebsite()->getCanonicalUrl());

            if ($job->getType()->getName() == 'Full site') {
                $resolvedUrl = $this->trimToRootUrl($resolvedUrl);
            }

            if ($job->getWebsite()->getCanonicalUrl() != $resolvedUrl) {
                $job->setWebsite($this->websiteService->fetch($resolvedUrl));
            }

            $job->setState($jobResolvedState);
        } catch (CurlException $curlException) {
            $this->jobService->reject($job, 'curl-' . $curlException->getErrorNo());
        }

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