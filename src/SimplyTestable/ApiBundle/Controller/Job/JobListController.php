<?php

namespace SimplyTestable\ApiBundle\Controller\Job;

use SimplyTestable\ApiBundle\Model\JobList\Configuration;
use SimplyTestable\ApiBundle\Request\Job\ListRequest;
use SimplyTestable\ApiBundle\Services\JobListService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JobListController extends BaseJobController
{
    /**
     * @param int $limit
     * @param int $offset
     *
     * @return Response
     */
    public function listAction($limit = null, $offset = null)
    {
        $jobListRequestFactory = $this->container->get('simplytestable.services.request.factory.job.list');
        $jobListRequest = $jobListRequestFactory->create();

        $jobListConfiguration = $this->createJobListConfiguration($jobListRequest);
        $jobListConfiguration->setLimit($limit);
        $jobListConfiguration->setOffset($offset);

        $jobListService = $this->container->get('simplytestable.services.joblistservice');
        $jobListService->setConfiguration($jobListConfiguration);

        return $this->sendResponse(array(
            'max_results' => $jobListService->getMaxResults(),
            'limit' => $jobListConfiguration->getLimit(),
            'offset' => $jobListConfiguration->getOffset(),
            'jobs' => $this->getJobSummaries($jobListService)
        ));
    }

    /**
     * @return Response
     */
    public function countAction()
    {
        $jobListRequestFactory = $this->container->get('simplytestable.services.request.factory.job.list');
        $jobListRequest = $jobListRequestFactory->create();

        $jobListConfiguration = $this->createJobListConfiguration($jobListRequest);

        $jobListService = $this->container->get('simplytestable.services.joblistservice');
        $jobListService->setConfiguration($jobListConfiguration);

        return new JsonResponse($jobListService->getMaxResults());
    }

    /**
     * @return Response
     */
    public function websitesAction()
    {
        $jobListRequestFactory = $this->container->get('simplytestable.services.request.factory.job.list');
        $jobListRequest = $jobListRequestFactory->create();

        $jobListConfiguration = $this->createJobListConfiguration($jobListRequest);

        $jobListService = $this->container->get('simplytestable.services.joblistservice');
        $jobListService->setConfiguration($jobListConfiguration);

        return new JsonResponse($jobListService->getWebsiteUrls());
    }

    /**
     * @param ListRequest $jobListRequest
     *
     * @return Configuration
     */
    private function createJobListConfiguration(ListRequest $jobListRequest)
    {
        $configuration = new Configuration([
            Configuration::KEY_USER => $this->getUser(),
            Configuration::KEY_TYPES_TO_EXCLUDE => $jobListRequest->getTypesToExclude(),
            Configuration::KEY_STATES_TO_EXCLUDE => $jobListRequest->getStatesToExclude(),
            Configuration::KEY_URL_FILTER => $jobListRequest->getUrlFilter(),
            Configuration::KEY_JOB_IDS_TO_EXCLUDE => $jobListRequest->getJobIdsToExclude(),
            Configuration::KEY_JOB_IDS_TO_INCLUDE => $jobListRequest->getJobIdsToInclude()
        ]);

        return $configuration;
    }

    /**
     * @param JobListService $jobListService
     *
     * @return array
     */
    private function getJobSummaries(JobListService $jobListService)
    {
        $jobs = $jobListService->get();
        $summaries = array();

        foreach ($jobs as $job) {
            $summaries[] = $this->getSummary($this->populateJob($job));
        }

        return $summaries;
    }
}
