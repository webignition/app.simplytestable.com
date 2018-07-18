<?php

namespace App\Controller\Job;

use App\Services\JobListConfigurationFactory;
use App\Services\JobListService;
use App\Services\JobSummaryFactory;
use App\Services\Request\Factory\Job\ListRequestFactory;
use Symfony\Component\HttpFoundation\JsonResponse;

class JobListController
{
    /**
     * @var JobListService
     */
    private $jobListService;

    /**
     * @var ListRequestFactory
     */
    private $jobListRequestFactory;

    /**
     * @var JobListConfigurationFactory
     */
    private $jobListConfigurationFactory;

    /**
     * @param JobListService $jobListService
     * @param ListRequestFactory $jobListRequestFactory
     * @param JobListConfigurationFactory $jobListConfigurationFactory
     */
    public function __construct(
        JobListService $jobListService,
        ListRequestFactory $jobListRequestFactory,
        JobListConfigurationFactory $jobListConfigurationFactory
    ) {
        $this->jobListService = $jobListService;
        $this->jobListRequestFactory = $jobListRequestFactory;
        $this->jobListConfigurationFactory = $jobListConfigurationFactory;
    }

    /**
     * @param JobSummaryFactory $jobSummaryFactory
     * @param int $limit
     * @param int $offset
     *
     * @return JsonResponse
     */
    public function listAction(
        JobSummaryFactory $jobSummaryFactory,
        $limit = null,
        $offset = null
    ) {
        $jobListRequest = $this->jobListRequestFactory->create();
        $jobListConfiguration = $this->jobListConfigurationFactory->createFromJobListRequest($jobListRequest);

        $jobListConfiguration->setLimit($limit);
        $jobListConfiguration->setOffset($offset);

        $this->jobListService->setConfiguration($jobListConfiguration);

        $jobs = $this->jobListService->get();

        $serializedJobSummaries = [];
        foreach ($jobs as $job) {
            $serializedJobSummaries[] = $jobSummaryFactory->create($job);
        }

        return new JsonResponse([
            'max_results' => $this->jobListService->getMaxResults(),
            'limit' => $jobListConfiguration->getLimit(),
            'offset' => $jobListConfiguration->getOffset(),
            'jobs' => $serializedJobSummaries,
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function countAction()
    {
        $jobListRequest = $this->jobListRequestFactory->create();
        $jobListConfiguration = $this->jobListConfigurationFactory->createFromJobListRequest($jobListRequest);

        $this->jobListService->setConfiguration($jobListConfiguration);

        return new JsonResponse($this->jobListService->getMaxResults());
    }

    /**
     * @return JsonResponse
     */
    public function websitesAction()
    {
        $jobListRequest = $this->jobListRequestFactory->create();
        $jobListConfiguration = $this->jobListConfigurationFactory->createFromJobListRequest($jobListRequest);

        $this->jobListService->setConfiguration($jobListConfiguration);

        return new JsonResponse($this->jobListService->getWebsiteUrls());
    }
}
