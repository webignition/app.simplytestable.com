<?php

namespace SimplyTestable\ApiBundle\Services\Request\Factory\Job;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Request\Job\ListRequest;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\JobService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\State;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ListRequestFactory
{
    const PARAMETER_EXCLUDE_TYPES = 'exclude-types';
    const PARAMETER_EXCLUDE_CURRENT = 'exclude-current';
    const PARAMETER_EXCLUDE_FINISHED = 'exclude-finished';
    const PARAMETER_EXCLUDE_STATES = 'exclude-states';
    const PARAMETER_URL_FILTER = 'url-filter';

    /**
     * @var ParameterBag
     */
    private $requestPayload;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var CrawlJobContainerService
     */
    private $crawlJobContainerService;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var bool
     */
    private $shouldExcludeCurrent;

    /**
     * @var bool
     */
    private $shouldExcludeFinished;

    /**
     * @param RequestStack $requestStack
     * @param EntityManager $entityManager
     * @param JobService $jobService
     * @param CrawlJobContainerService $crawlJobContainerService
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        RequestStack $requestStack,
        EntityManager $entityManager,
        JobService $jobService,
        CrawlJobContainerService $crawlJobContainerService,
        TokenStorageInterface $tokenStorage
    ) {
        $request = $requestStack->getCurrentRequest();
        $this->requestPayload = $request->query;

        $this->entityManager = $entityManager;
        $this->jobService = $jobService;
        $this->crawlJobContainerService = $crawlJobContainerService;
        $this->tokenStorage = $tokenStorage;

        $this->shouldExcludeCurrent = !is_null($this->requestPayload->get(self::PARAMETER_EXCLUDE_CURRENT));
        $this->shouldExcludeFinished = !is_null($this->requestPayload->get(self::PARAMETER_EXCLUDE_FINISHED));
    }

    /**
     * @return ListRequest
     */
    public function create()
    {
        return new ListRequest(
            $this->getTypesToExcludeFromRequest(),
            $this->getStatesToExcludeFromRequest(),
            $this->requestPayload->get(self::PARAMETER_URL_FILTER),
            $this->getJobIdsToExclude(),
            $this->getJobIdsToInclude()
        );
    }

    /**
     * @return JobType[]
     */
    private function getTypesToExcludeFromRequest()
    {
        $excludeTypeNames = $this->requestPayload->get(self::PARAMETER_EXCLUDE_TYPES);
        if (is_null($excludeTypeNames)) {
            $excludeTypeNames = [];
        }

        if (!in_array('crawl', $excludeTypeNames)) {
            $excludeTypeNames[] = 'crawl';
        }

        $jobTypeRepository = $this->entityManager->getRepository(JobType::class);

        return $jobTypeRepository->findBy([
            'name' => $excludeTypeNames,
        ]);
    }

    /**
     * @return State[]
     */
    private function getStatesToExcludeFromRequest()
    {
        $stateNamesToExclude = $this->getStateNamesToExcludeFromRequest();

        $stateRepository = $this->entityManager->getRepository(State::class);

        return $stateRepository->findBy([
            'name' => $stateNamesToExclude,
        ]);
    }

    /**
     *
     * @return string[]
     */
    private function getStateNamesToExcludeFromRequest()
    {
        $stateNamesToExclude = [];

        if ($this->shouldExcludeCurrent) {
            $stateNamesToExclude = array_merge(
                $stateNamesToExclude,
                $this->jobService->getIncompleteStateNames()
            );
        }

        if ($this->shouldExcludeFinished) {
            $stateNamesToExclude = array_merge(
                $stateNamesToExclude,
                $this->jobService->getFinishedStateNames()
            );
        }

        $requestExcludeStateNames = $this->requestPayload->get(self::PARAMETER_EXCLUDE_STATES);

        if (!empty($requestExcludeStateNames)) {
            foreach ($requestExcludeStateNames as $truncatedStateName) {
                $stateName = 'job-' . $truncatedStateName;
                if (!in_array($stateName, $stateNamesToExclude)) {
                    $stateNamesToExclude[] = $stateName;
                }
            }
        }

        return $stateNamesToExclude;
    }

    /**
     * @return int[]
     */
    private function getJobIdsToExclude()
    {
        return $this->shouldExcludeCurrent
            ? $this->getCrawlJobParentIds()
            : [];
    }


    /**
     * @return int[]
     */
    private function getJobIdsToInclude()
    {
        return $this->shouldExcludeCurrent
            ? []
            : $this->getCrawlJobParentIds();
    }

    /**
     * @return int[]
     */
    private function getCrawlJobParentIds()
    {
        $user = $this->tokenStorage->getToken()->getUser();

        $crawlJobParentIds = array();
        $crawlJobContainers = $this->crawlJobContainerService->getAllActiveForUser($user);

        foreach ($crawlJobContainers as $crawlJobContainer) {
            $crawlJobParentIds[] = $crawlJobContainer->getParentJob()->getId();
        }

        return $crawlJobParentIds;
    }
}
