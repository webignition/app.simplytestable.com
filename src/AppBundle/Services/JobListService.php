<?php
namespace AppBundle\Services;

use AppBundle\Entity\Job\Job;
use AppBundle\Model\JobList\Configuration;
use AppBundle\Services\QueryBuilderFactory\JobListQueryBuilderFactory;
use AppBundle\Services\Team\Service as TeamService;

class JobListService
{
    const EXCEPTION_MESSAGE_CONFIGURATION_NOT_SET = 'Configuration not set';
    const EXCEPTION_CODE_CONFIGURATION_NOT_SET = 1;

    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var TeamService
     */
    private $teamService;

    /**
     * @var JobListQueryBuilderFactory
     */
    private $queryBuilderFactory;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param JobService $jobService
     * @param TeamService $teamService
     * @param JobListQueryBuilderFactory $jobListQueryBuilderFactory
     */
    public function __construct(
        JobService $jobService,
        TeamService $teamService,
        JobListQueryBuilderFactory $jobListQueryBuilderFactory
    ) {
        $this->jobService = $jobService;
        $this->teamService = $teamService;
        $this->queryBuilderFactory = $jobListQueryBuilderFactory;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return Job[]
     */
    public function get()
    {
        if (empty($this->configuration)) {
            throw new \RuntimeException(
                self::EXCEPTION_MESSAGE_CONFIGURATION_NOT_SET,
                self::EXCEPTION_CODE_CONFIGURATION_NOT_SET
            );
        }

        $queryBuilder = $this->queryBuilderFactory->create($this->configuration);
        $queryBuilder->select('Job');

        $queryBuilder->setMaxResults($this->configuration->getLimit());
        $queryBuilder->setFirstResult($this->configuration->getOffset());

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return int
     */
    public function getMaxResults()
    {
        if (empty($this->configuration)) {
            throw new \RuntimeException(
                self::EXCEPTION_MESSAGE_CONFIGURATION_NOT_SET,
                self::EXCEPTION_CODE_CONFIGURATION_NOT_SET
            );
        }

        $queryBuilder = $this->queryBuilderFactory->create($this->configuration);
        $queryBuilder->select('COUNT(Job.id)');

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }

    /**
     * @return string[]
     */
    public function getWebsiteUrls()
    {
        if (empty($this->configuration)) {
            throw new \RuntimeException(
                self::EXCEPTION_MESSAGE_CONFIGURATION_NOT_SET,
                self::EXCEPTION_CODE_CONFIGURATION_NOT_SET
            );
        }

        $queryBuilder = $this->queryBuilderFactory->create($this->configuration);

        if (!substr_count($queryBuilder->getDQL(), 'JOIN Job.website Website')) {
            $queryBuilder->join('Job.website', 'Website');
        }

        $queryBuilder->orderBy('Website.canonicalUrl');
        $queryBuilder->select('DISTINCT Website.canonicalUrl');

        $results = $queryBuilder->getQuery()->getResult();

        $urls = [];

        foreach ($results as $result) {
            $urls[] = $result['canonicalUrl'];
        }

        return $urls;
    }
}
