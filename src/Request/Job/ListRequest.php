<?php

namespace App\Request\Job;

use App\Entity\Job\Type as JobType;
use App\Entity\State;
use App\Entity\User;

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
     * @var User
     */
    private $user;

    /**
     * @param JobType[] $typesToExclude
     * @param State[] $statesToExclude
     * @param string $urlFilter
     * @param int[] $jobIdsToExclude
     * @param int[] $jobIdsToInclude
     * @param User $user
     */
    public function __construct(
        $typesToExclude,
        $statesToExclude,
        $urlFilter,
        $jobIdsToExclude,
        $jobIdsToInclude,
        User $user
    ) {
        $this->typesToExclude = $typesToExclude;
        $this->statesToExclude = $statesToExclude;
        $this->urlFilter = $urlFilter;
        $this->jobIdsToExclude = $jobIdsToExclude;
        $this->jobIdsToInclude = $jobIdsToInclude;
        $this->user = $user;
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

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
