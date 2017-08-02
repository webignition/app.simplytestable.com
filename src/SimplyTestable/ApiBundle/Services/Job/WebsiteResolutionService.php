<?php
namespace SimplyTestable\ApiBundle\Services\Job;

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
     * @var RejectionService
     */
    private $jobRejectionService;

    /**
     * @var UrlResolver
     */
    private $urlResolver = null;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @param JobService $jobService
     * @param HttpClientService $httpClientService
     * @param WebSiteService $websiteService
     * @param RejectionService $jobRejectionService
     * @param UrlResolver $urlResolver
     * @param StateService $stateService
     */
    public function __construct(
        JobService $jobService,
        HttpClientService $httpClientService,
        WebSiteService $websiteService,
        RejectionService $jobRejectionService,
        UrlResolver $urlResolver,
        StateService $stateService
    ) {
        $this->jobService = $jobService;
        $this->httpClientService = $httpClientService;
        $this->websiteService = $websiteService;
        $this->jobRejectionService = $jobRejectionService;
        $this->urlResolver = $urlResolver;
        $this->stateService = $stateService;
    }

    /**
     * @param Job $job
     * @throws WebsiteResolutionException
     */
    public function resolve(Job $job)
    {
        if (!$this->jobService->isNew($job)) {
            throw new WebsiteResolutionException(
                'Job is in wrong state, currently "'.$job->getState()->getName().'"',
                WebsiteResolutionException::CODE_JOB_IN_WRONG_STATE_CODE
            );
        }

        $jobResolvingState = $this->stateService->fetch(JobService::RESOLVING_STATE);
        $jobResolvedState = $this->stateService->fetch(JobService::RESOLVED_STATE);

        $job->setState($jobResolvingState);
        $this->jobService->persistAndFlush($job);

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
            $this->jobRejectionService->reject($job, 'curl-' . $curlException->getErrorNo());
        }


        $this->jobService->persistAndFlush($job);
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