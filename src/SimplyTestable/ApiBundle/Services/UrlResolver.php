<?php

namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use webignition\Url\Resolver\Resolver;

class UrlResolver extends Resolver
{
    const USER_AGENT = 'ST url resolver (http://bit.ly/RlhKCL)';
    const CURL_TIMEOUT_MS = 10000;

    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @param HttpClientService $httpClientService
     */
    public function __construct(HttpClientService $httpClientService)
    {
        $baseRequest = $httpClientService->getRequest();
        $baseRequest->getCurlOptions()->set(CURLOPT_TIMEOUT_MS, self::CURL_TIMEOUT_MS);
        $baseRequest->setHeader('user-agent', self::USER_AGENT);

        $resolverConfiguration = $this->getConfiguration();

        $resolverConfiguration->enableFollowMetaRedirects();
        $resolverConfiguration->enableRetryWithUrlEncodingDisabled();
        $resolverConfiguration->setBaseRequest($baseRequest);

        $this->httpClientService = $httpClientService;
    }

    /**
     * @param Job $job
     */
    public function configureForJob(Job $job)
    {
        $configuration = $this->getConfiguration();

        if ($job->hasParameter('cookies')) {
            $configuration->setCookies($job->getParameter('cookies'));
        }

        if ($job->hasParameter('http-auth-username') || $job->hasParameter('http-auth-password')) {
            $this->httpClientService->prepareRequest(
                $configuration->getBaseRequest(),
                $job->getParametersArray()
            );
        }
    }
}
