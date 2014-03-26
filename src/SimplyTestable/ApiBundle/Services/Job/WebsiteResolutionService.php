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
     * @var \SimplyTestable\ApiBundle\Services\Job\RejectionService
     */
    private $jobRejectionService;    
    
    
    /**
     *
     * @var \webignition\Url\Resolver\Resolver
     */
    private $urlResolver = null;
    
    
    public function __construct(
        \SimplyTestable\ApiBundle\Services\JobService $jobService,
        \SimplyTestable\ApiBundle\Services\HttpClientService $httpClientService,
        \SimplyTestable\ApiBundle\Services\WebSiteService $websiteService,
        \SimplyTestable\ApiBundle\Services\Job\RejectionService $jobRejectionService
    ) {
        $this->jobService = $jobService;
        $this->httpClientService = $httpClientService;
        $this->websiteService = $websiteService;
        $this->jobRejectionService = $jobRejectionService;
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
        
        try {
            $resolvedUrl = $this->getUrlResolver($job)->resolve($job->getWebsite()->getCanonicalUrl());

            if ($job->getType()->getName() == 'Full site') {
                $resolvedUrl = $this->trimToRootUrl($resolvedUrl);
            }        

            if ($job->getWebsite()->getCanonicalUrl() != $resolvedUrl) {            
                $job->setWebsite($this->websiteService->fetch($resolvedUrl));
            }

            $job->setState($this->jobService->getResolvedState());            
        } catch (\Guzzle\Http\Exception\CurlException $curlException) {                        
            $this->jobRejectionService->reject($job, 'curl-' . $curlException->getErrorNo());
        }
        

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
    public function getUrlResolver(Job $job) {        
        if (is_null($this->urlResolver)) {
            $this->urlResolver = new \webignition\Url\Resolver\Resolver();
            
            $baseRequest = $this->httpClientService->getRequest();            
            $baseRequest->getCurlOptions()->set(CURLOPT_TIMEOUT_MS, 10000);
            
            $this->urlResolver->getConfiguration()->enableFollowMetaRedirects();
            $this->urlResolver->getConfiguration()->enableRetryWithUrlEncodingDisabled();
            $this->urlResolver->getConfiguration()->setBaseRequest($baseRequest);
            
            if ($job->hasParameter('cookies')) {
                $this->urlResolver->getConfiguration()->setCookies($job->getParameter('cookies'));
            }
        }
        
        if ($job->hasParameter('http-auth-username') || $job->hasParameter('http-auth-password')) {            
            $this->urlResolver->getConfiguration()->getBaseRequest()->setAuth(
                $job->hasParameter('http-auth-username') ? $job->getParameter('http-auth-username') : '',
                $job->hasParameter('http-auth-password') ? $job->getParameter('http-auth-password') : '',
                'any'
            );
        }
        
        return $this->urlResolver;
    }
}