<?php

namespace SimplyTestable\ApiBundle\Controller\Job;

use SimplyTestable\ApiBundle\Services\JobListConfigurationFactory;
use SimplyTestable\ApiBundle\Services\JobListService;
use SimplyTestable\ApiBundle\Services\JobSummaryFactory;
use SimplyTestable\ApiBundle\Services\Request\Factory\Job\ListRequestFactory;
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
        $jobListRequestFactory = $this->container->get(ListRequestFactory::class);
        $jobListConfigurationFactory = $this->container->get(JobListConfigurationFactory::class);
        $jobSummaryFactory = $this->container->get(JobSummaryFactory::class);
        $jobListService = $this->container->get(JobListService::class);

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
        $jobListRequestFactory = $this->container->get(ListRequestFactory::class);
        $jobListConfigurationFactory = $this->container->get(JobListConfigurationFactory::class);

        $jobListRequest = $jobListRequestFactory->create();
        $jobListConfiguration = $jobListConfigurationFactory->createFromJobListRequest($jobListRequest);

        $jobListService = $this->container->get(JobListService::class);
        $jobListService->setConfiguration($jobListConfiguration);

        return new JsonResponse($jobListService->getMaxResults());
    }

    /**
     * @return Response
     */
    public function websitesAction()
    {
        $jobListRequestFactory = $this->container->get(ListRequestFactory::class);
        $jobListConfigurationFactory = $this->container->get(JobListConfigurationFactory::class);

        $jobListRequest = $jobListRequestFactory->create();
        $jobListConfiguration = $jobListConfigurationFactory->createFromJobListRequest($jobListRequest);

        $jobListService = $this->container->get(JobListService::class);
        $jobListService->setConfiguration($jobListConfiguration);

        return new JsonResponse($jobListService->getWebsiteUrls());
    }
}
