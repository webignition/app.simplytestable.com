<?php

namespace SimplyTestable\ApiBundle\Request\Job;

use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\State;

class ListRequest
{
    /**
     * @var JobType[]
     */
    private $typesToExclude = [];

    /**
     * @var State[]
     */
    private $statesToExclude = [];

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

    /**
     * @return int[]
     */
    public function getJobIdsToExclude()
    {
        return $this->jobIdsToExclude;
    }

    /**
     * @return int[]
     */
    public function getJobIdsToInclude()
    {
        return $this->jobIdsToInclude;
    }
}
