<?php
namespace App\Services\Job;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\ConnectException;
use App\Entity\Job\Job;
use App\Exception\Services\Job\WebsiteResolutionException;
use App\Services\HttpClientService;
use App\Services\JobService;
use App\Services\StateService;
use App\Services\WebSiteService;
use GuzzleHttp\Exception\RequestException;
use webignition\Uri\Uri;
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
        $jobIsNew = Job::STATE_STARTING === (string) $job->getState();

        if (!$jobIsNew) {
            throw new WebsiteResolutionException(
                'Job is in wrong state, currently "' . (string) $job->getState() . '"',
                WebsiteResolutionException::CODE_JOB_IN_WRONG_STATE_CODE
            );
        }

        $jobResolvingState = $this->stateService->get(Job::STATE_RESOLVING);
        $jobResolvedState = $this->stateService->get(Job::STATE_RESOLVED);

        $job->setState($jobResolvingState);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $jobParametersObject = $job->getParameters();

        $cookies = $jobParametersObject->getCookies();
        if (!empty($cookies)) {
            $this->httpClientService->setCookies($cookies);
        }

        $httpAuthenticationCredentials = $jobParametersObject->getHttpAuthenticationCredentials(
            $job->getWebsite()->getCanonicalUrl()
        );
        if (!$httpAuthenticationCredentials->isEmpty()) {
            $this->httpClientService->setBasicHttpAuthorization($httpAuthenticationCredentials);
        }

        $this->httpClientService->setRequestHeader('User-Agent', self::URL_RESOLVER_USER_AGENT);

        try {
            $jobUri = new Uri($job->getWebsite()->getCanonicalUrl());
            $resolvedJobUri = $this->urlResolver->resolve($jobUri);

            if ($job->getType()->getName() == 'Full site') {
                $resolvedJobUri = $resolvedJobUri->withPath('/');
                $resolvedJobUri = $resolvedJobUri->withQuery('');
                $resolvedJobUri = $resolvedJobUri->withFragment('');
            }

            if ((string) $jobUri !== (string) $resolvedJobUri) {
                $job->setWebsite($this->websiteService->get((string) $resolvedJobUri));
            }

            $job->setState($jobResolvedState);
        } catch (ConnectException $connectException) {
            $curlException = GuzzleCurlExceptionFactory::fromConnectException($connectException);

            $this->jobService->reject($job, 'curl-' . $curlException->getCurlCode());
        } catch (RequestException $requestException) {
            $curlExceptionMatches = [];
            $curlExceptionMessagePattern = '/^cURL error [0-9]+/';

            if (preg_match($curlExceptionMessagePattern, $requestException->getMessage(), $curlExceptionMatches)) {
                $curlCode = (int) preg_replace('/\D/', '', $curlExceptionMatches[0]);

                $this->jobService->reject($job, 'curl-' . $curlCode);
            } else {
                throw $requestException;
            }
        }

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }
}
