<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use Guzzle\Http\Exception\CurlException;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Exception\Services\Job\WebsiteResolutionException;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\JobService;
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
     * @param JobService $jobService
     * @param HttpClientService $httpClientService
     * @param WebSiteService $websiteService
     * @param RejectionService $jobRejectionService
     * @param UrlResolver $urlResolver
     */
    public function __construct(
        JobService $jobService,
        HttpClientService $httpClientService,
        WebSiteService $websiteService,
        RejectionService $jobRejectionService,
        UrlResolver $urlResolver
    ) {
        $this->jobService = $jobService;
        $this->httpClientService = $httpClientService;
        $this->websiteService = $websiteService;
        $this->jobRejectionService = $jobRejectionService;
        $this->urlResolver = $urlResolver;
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

        $job->setState($this->jobService->getResolvingState());
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

            $job->setState($this->jobService->getResolvedState());
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