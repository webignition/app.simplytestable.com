<?php

namespace SimplyTestable\ApiBundle\Controller\Job;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JobListController extends Controller
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
        $jobListConfigurationFactory = $this->container->get('simplytestable.services.joblistconfigurationfactory');
        $jobSummaryFactory = $this->container->get('simplytestable.services.jobsummaryfactory');
        $jobListService = $this->container->get('simplytestable.services.joblistservice');

        $jobListRequest = $jobListRequestFactory->create();
        $jobListConfiguration = $jobListConfigurationFactory->createFromJobListRequest($jobListRequest);

        $jobListConfiguration->setLimit($limit);
        $jobListConfiguration->setOffset($offset);

        $jobListService->setConfiguration($jobListConfiguration);

        $jobs = $jobListService->get();

        $serializedJobSummaries = [];
        foreach ($jobs as $job) {
            $serializedJobSummaries[] = $jobSummaryFactory->create($job);
        }

        return new JsonResponse([
            'max_results' => $jobListService->getMaxResults(),
            'limit' => $jobListConfiguration->getLimit(),
            'offset' => $jobListConfiguration->getOffset(),
            'jobs' => $serializedJobSummaries,
        ]);
    }

    /**
     * @return Response
     */
    public function countAction()
    {
        $jobListRequestFactory = $this->container->get('simplytestable.services.request.factory.job.list');
        $jobListConfigurationFactory = $this->container->get('simplytestable.services.joblistconfigurationfactory');

        $jobListRequest = $jobListRequestFactory->create();
        $jobListConfiguration = $jobListConfigurationFactory->createFromJobListRequest($jobListRequest);

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
        $jobListConfigurationFactory = $this->container->get('simplytestable.services.joblistconfigurationfactory');

        $jobListRequest = $jobListRequestFactory->create();
        $jobListConfiguration = $jobListConfigurationFactory->createFromJobListRequest($jobListRequest);

        $jobListService = $this->container->get('simplytestable.services.joblistservice');
        $jobListService->setConfiguration($jobListConfiguration);

        return new JsonResponse($jobListService->getWebsiteUrls());
    }
}
