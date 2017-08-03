<?php

namespace SimplyTestable\ApiBundle\Controller\Job;

use SimplyTestable\ApiBundle\Entity\Job\Type;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Model\JobList\Configuration;
use SimplyTestable\ApiBundle\Services\JobListService;
use SimplyTestable\ApiBundle\Services\StateService;
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
        $jobListConfiguration = $this->createJobListConfiguration();
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
        $jobListService = $this->container->get('simplytestable.services.joblistservice');
        $jobListService->setConfiguration($this->createJobListConfiguration());

        return $this->sendResponse($jobListService->getMaxResults());
    }

    /**
     * @return Response
     */
    public function websitesAction()
    {
        $jobListService = $this->container->get('simplytestable.services.joblistservice');
        $jobListService->setConfiguration($this->createJobListConfiguration());

        return $this->sendResponse($jobListService->getWebsiteUrls());
    }

    /**
     * @return Configuration
     */
    private function createJobListConfiguration()
    {
        $configuration = new Configuration([
            Configuration::KEY_USER => $this->getUser(),
            Configuration::KEY_TYPES_TO_EXCLUDE => $this->getExcludeTypes(),
            Configuration::KEY_STATES_TO_EXCLUDE => $this->getExcludeStates(),
            Configuration::KEY_URL_FILTER => $this->get('request')->query->get('url-filter'),
        ]);

        $crawlJobParentIds = $this->getCrawlJobParentIds();

        if ($this->shouldExcludeCurrent()) {
            $configuration->setJobIdsToExclude($crawlJobParentIds);
        } else {
            $configuration->setJobIdsToInclude($crawlJobParentIds);
        }

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

    /**
     * @return int[]
     */
    private function getCrawlJobParentIds()
    {
        $crawlJobParentIds = array();
        $crawlJobContainers = $this->getCrawlJobContainerService()->getAllActiveForUser($this->getUser());

        foreach ($crawlJobContainers as $crawlJobContainer) {
            $crawlJobParentIds[] = $crawlJobContainer->getParentJob()->getId();
        }

        return $crawlJobParentIds;
    }

    /**
     * @return string[]
     */
    private function getExcludeTypeNames()
    {
        $excludeTypeNames = (is_null($this->get('request')->query->get('exclude-types')))
            ? []
            : $this->get('request')->query->get('exclude-types');

        if (!in_array('crawl', $excludeTypeNames)) {
            $excludeTypeNames[] = 'crawl';
        }

        return $excludeTypeNames;
    }

    /**
     * @return Type[]
     */
    private function getExcludeTypes()
    {
        $excludeTypes = array();

        foreach ($this->getExcludeTypeNames() as $typeName) {
            if ($this->getJobTypeService()->has($typeName)) {
                $excludeTypes[] = $this->getJobTypeService()->getByName($typeName);
            }
        }

        return $excludeTypes;
    }

    /**
     * @return bool
     */
    private function shouldExcludeCurrent()
    {
        return !is_null($this->get('request')->query->get('exclude-current'));
    }

    /**
     *
     * @return string[]
     */
    private function getExcludeStateNames()
    {
        $excludeStateNames = array();
        if ($this->shouldExcludeCurrent()) {
            $excludeStateNames = array_merge(
                $excludeStateNames,
                $this->getStateNames($this->getJobService()->getIncompleteStates())
            );
        }

        if (!is_null($this->get('request')->query->get('exclude-finished'))) {
            $excludeStateNames = array_merge(
                $excludeStateNames,
                $this->getStateNames($this->getJobService()->getFinishedStates())
            );
        }

        if (!is_null($this->get('request')->query->get('exclude-states'))) {
            $truncatedStateNames = $this->get('request')->query->get('exclude-states');
            foreach ($truncatedStateNames as $truncatedStateName) {
                $stateName = 'job-' . $truncatedStateName;
                if (!in_array($stateName, $excludeStateNames)) {
                    $excludeStateNames[] = $stateName;
                }
            }
        }

        return $excludeStateNames;
    }

    /**
     * @return State[]
     */
    private function getExcludeStates()
    {
        $excludeStates = array();
        foreach ($this->getExcludeStateNames() as $stateName) {
            if ($this->getStateService()->has($stateName)) {
                $excludeStates[] = $this->getStateService()->fetch($stateName);
            }
        }

        return $excludeStates;
    }

    /**
     * @param array $states
     * @return string[]
     */
    private function getStateNames($states)
    {
        $stateNames = array();

        foreach ($states as $state) {
            if (!in_array($state->getName(), $stateNames)) {
                $stateNames[] = $state->getName();
            }
        }

        return $stateNames;
    }

    /**
     * @return StateService
     */
    private function getStateService()
    {
        return $this->get('simplytestable.services.stateservice');
    }
}
