<?php

namespace SimplyTestable\ApiBundle\Request\Job;

use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\State;

class ListRequest
{
    // exclude-types
    // exclude-current
    // exclude-finished
    // exclude-states
    // url-filter

    /**
     * @var JobType[]
     */
    private $typesToExclude = [];

    /**
     * @var State[]
     */
    private $statesToExclude = [];

    /**
     * Should be used by factory to form collection of states to exclude
     * Should be used by factory to form collection of job ids to include/exclude
     *
     * if true:
     * exclude crawl job parent ids
     * include incomplete states in states to exclude
     *
     * if false:
     * include crawl job parent ids
     *
     * @var bool
     */
    private $shouldExcludeCurrent;

    /**
     * Should be used by factory to form collection of states to exclude
     *
     * if true:
     * include complete states in states to exclude
     *
     * @var bool
     */
    private $shouldExcludeFinished;

    /**
     * @var int[]
     */
    private $jobIdsToExclude;

    /**
     * @var int[]
     */
    private $jobIdsToInclude;

    /**
     * @var string
     */
    private $urlFilter;

    /**
     * @param JobType[] $typesToExclude
     * @param State[] $statesToExclude
     * @param string $urlFilter
     * @param int[] $jobIdsToExclude
     * @param int[] $jobIdsToInclude
     */
    public function __construct(
        $typesToExclude,
        $statesToExclude,
        $urlFilter,
        $jobIdsToExclude,
        $jobIdsToInclude
    ) {
        $this->typesToExclude = $typesToExclude;
        $this->statesToExclude = $statesToExclude;
        $this->urlFilter = $urlFilter;
        $this->jobIdsToExclude = $jobIdsToExclude;
        $this->jobIdsToInclude = $jobIdsToInclude;
    }

    /**
     * @return JobType[]
     */
    public function getTypesToExclude()
    {
        return $this->typesToExclude;
    }

    /**
     * @return State[]
     */
    public function getStatesToExclude()
    {
        return $this->statesToExclude;
    }

    /**
     * @return string
     */
    public function getUrlFilter()
    {
        return $this->urlFilter;
    }
}
