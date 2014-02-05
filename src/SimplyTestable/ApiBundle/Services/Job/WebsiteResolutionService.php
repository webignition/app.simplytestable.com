<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Exception\Services\Job\WebsiteResolutionException;

class WebsiteResolutionService {
    
    const RETRUN_CODE_OK = 0;
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\JobService
     */
    private $jobService;    
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\HttpClientService
     */
    private $httpClientService;      
  
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\WebSiteService
     */
    private $websiteService;
    
    
    /**
     *
     * @var \webignition\Url\Resolver\Resolver
     */
    private $urlResolver = null;
    
    
    public function __construct(
        \SimplyTestable\ApiBundle\Services\JobService $jobService,
        \SimplyTestable\ApiBundle\Services\HttpClientService $httpClientService,
        \SimplyTestable\ApiBundle\Services\WebSiteService $websiteService
    ) {
        $this->jobService = $jobService;
        $this->httpClientService = $httpClientService;
        $this->websiteService = $websiteService;
    }
    
    
    public function resolve(Job $job) {
        if (!$this->jobService->isNew($job)) {
            throw new WebsiteResolutionException(
                'Job is in wrong state, currently "'.$job->getState()->getName().'"',
                WebsiteResolutionException::CODE_JOB_IN_WRONG_STATE_CODE
            );
        }
        
        $job->setState($this->jobService->getResolvingState());
        $this->jobService->persistAndFlush($job);
        
        $resolvedUrl = $this->getUrlResolver()->resolve($job->getWebsite()->getCanonicalUrl());
        
        if ($job->getType()->getName() == 'Full site') {
            $resolvedUrl = $this->trimToRootUrl($resolvedUrl);
        }        
        
        if ($job->getWebsite()->getCanonicalUrl() != $resolvedUrl) {            
            if (!$this->websiteService->has($resolvedUrl)) {
                $this->websiteService->create($resolvedUrl);
            }
            
            $job->setWebsite($this->websiteService->fetch($resolvedUrl));
        }
        
        $job->setState($this->jobService->getResolvedState());
        $this->jobService->persistAndFlush($job);        
    }
    
    
    /**
     * 
     * @param string $url
     * @return string
     */
    private function trimToRootUrl($url) {
        $urlObject = new \webignition\Url\Url($url);
        return $urlObject->getScheme() . '://' . $urlObject->getHost() . '/';
    }
    
    
    /**
     * @return \webignition\Url\Resolver\Resolver
     */
    private function getUrlResolver() {
        if (is_null($this->urlResolver)) {
            $this->urlResolver = new \webignition\Url\Resolver\Resolver();
            $this->urlResolver->getConfiguration()->enableFollowMetaRedirects();
            $this->urlResolver->getConfiguration()->enableRetryWithUrlEncodingDisabled();
            $this->urlResolver->getConfiguration()->setBaseRequest($this->httpClientService->getRequest());
        }
        
        return $this->urlResolver;
    }
}