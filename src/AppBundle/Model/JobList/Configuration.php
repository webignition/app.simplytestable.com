<?php
namespace AppBundle\Model\JobList;

use AppBundle\Entity\Job\Type as JobType;
use AppBundle\Entity\User;
use AppBundle\Entity\State;

class Configuration
{
    const DEFAULT_LIMIT = 1;
    const MIN_LIMIT = 1;
    const MAX_LIMIT = 100;

    const DEFAULT_OFFSET = 0;
    const MIN_OFFSET = 0;

    const KEY_LIMIT = 'limit';
    const KEY_OFFSET = 'offset';
    const KEY_USER = 'user';
    const KEY_TYPES_TO_EXCLUDE = 'types-to-exclude';
    const KEY_STATES_TO_EXCLUDE = 'states-to-exclude';
    const KEY_JOB_IDS_TO_INCLUDE = 'job-ids-to-include';
    const KEY_JOB_IDS_TO_EXCLUDE = 'job-ids-to-exclude';
    const KEY_URL_FILTER = 'url-filter';

    /**
     * @var int
     */
    private $limit = self::DEFAULT_LIMIT;

    /**
     * @var int
     */
    private $offset = self::DEFAULT_OFFSET;

    /**
     * @var User
     */
    private $user = null;

    /**
     * Collection of JobTypes to exclude from list
     *
     * @var JobType[]
     */
    private $typesToExclude = [];

    /**
     * Collection of Job States to exclude from list
     *
     * @var State[]
     */
    private $statesToExclude = [];

    /**
     * Collection of ids of jobs to include if not otherwise included
     *
     * @var int[]
     */
    private $jobIdsToInclude = [];

    /**
     * Explicitly exclude jobs by id
     *
     * @var int[]
     */
    private $jobIdsToExclude = [];

    /**
     * @var string
     */
    private $urlFilter = null;

    /**
     * @param array $configurationValues
     */
    public function __construct($configurationValues = [])
    {
        if (isset($configurationValues[self::KEY_LIMIT])) {
            $this->setLimit($configurationValues[self::KEY_LIMIT]);
        }

        if (isset($configurationValues[self::KEY_OFFSET])) {
            $this->setOffset($configurationValues[self::KEY_OFFSET]);
        }

        if (isset($configurationValues[self::KEY_USER])) {
            $this->setUser($configurationValues[self::KEY_USER]);
        }

        if (isset($configurationValues[self::KEY_TYPES_TO_EXCLUDE])) {
            $this->setTypesToExclude($configurationValues[self::KEY_TYPES_TO_EXCLUDE]);
        }

        if (isset($configurationValues[self::KEY_STATES_TO_EXCLUDE])) {
            $this->setStatesToExclude($configurationValues[self::KEY_STATES_TO_EXCLUDE]);
        }

        if (isset($configurationValues[self::KEY_JOB_IDS_TO_INCLUDE])) {
            $this->setJobIdsToInclude($configurationValues[self::KEY_JOB_IDS_TO_INCLUDE]);
        }

        if (isset($configurationValues[self::KEY_JOB_IDS_TO_EXCLUDE])) {
            $this->setJobIdsToExclude($configurationValues[self::KEY_JOB_IDS_TO_EXCLUDE]);
        }

        if (isset($configurationValues[self::KEY_URL_FILTER])) {
            $this->setUrlFilter($configurationValues[self::KEY_URL_FILTER]);
        }
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $limit = (int)filter_var($limit, FILTER_VALIDATE_INT);

        if ($limit < self::MIN_LIMIT) {
            $limit = self::MIN_LIMIT;
        }

        if ($limit > self::MAX_LIMIT) {
            $limit = self::MAX_LIMIT;
        }

        $this->limit = $limit;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $offset = (int)filter_var($offset, FILTER_VALIDATE_INT);

        if ($offset < self::MIN_OFFSET) {
            $offset = self::MIN_OFFSET;
        }

        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param JobType[] $typesToExclude
     */
    public function setTypesToExclude($typesToExclude)
    {
        $this->typesToExclude = [];

        foreach ($typesToExclude as $jobType) {
            if ($jobType instanceof JobType) {
                $this->typesToExclude[] = $jobType;
            }
        }
    }

    /**
     * @return JobType[]
     */
    public function getTypesToExclude()
    {
        return $this->typesToExclude;
    }

    /**
     * @param State[] $statesToExclude
     */
    public function setStatesToExclude($statesToExclude)
    {
        $this->statesToExclude = [];

        foreach ($statesToExclude as $state) {
            if ($state instanceof State) {
                $this->statesToExclude[] = $state;
            }
        }
    }

    /**
     * @return State[]
     */
    public function getStatesToExclude()
    {
        return $this->statesToExclude;
    }

    /**
     * @param int[] $jobIdsToInclude
     */
    public function setJobIdsToInclude($jobIdsToInclude)
    {
        $this->jobIdsToInclude = $jobIdsToInclude;
    }

    /**
     * @return int[]
     */
    public function getJobIdsToInclude()
    {
        return $this->jobIdsToInclude;
    }

    /**
     * @param int[] $jobIdsToExclude
     */
    public function setJobIdsToExclude($jobIdsToExclude)
    {
        $this->jobIdsToExclude = $jobIdsToExclude;
    }

    /**
     * @return int[]
     */
    public function getJobIdsToExclude()
    {
        return $this->jobIdsToExclude;
    }

    /**
     * @param string $filter
     */
    public function setUrlFilter($filter)
    {
        $this->urlFilter = $filter;
    }

    /**
     * @return string
     */
    public function getUrlFilter()
    {
        return $this->urlFilter;
    }
}
