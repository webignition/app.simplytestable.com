<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;

class RetrievalService
{
    /**
     * @var TeamService
     */
    private $teamService;

    /**
     * @var User
     */
    private $user = null;

    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @param TeamService $teamService
     * @param JobRepository $jobRepository
     */
    public function __construct(TeamService $teamService, JobRepository $jobRepository)
    {
        $this->teamService = $teamService;
        $this->jobRepository = $jobRepository;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param int $jobId
     * @throws JobRetrievalServiceException
     *
     * @return Job
     */
    public function retrieve($jobId)
    {
        if (!$this->hasUser()) {
            throw new JobRetrievalServiceException(
                'User not set',
                JobRetrievalServiceException::CODE_USER_NOT_SET
            );
        }

        $job = $this->jobRepository->find($jobId);
        if (!$job instanceof Job) {
            throw new JobRetrievalServiceException(
                'Job [' . $jobId . '] not found',
                JobRetrievalServiceException::CODE_JOB_NOT_FOUND
            );
        }

        if (!$this->isAuthorised($job)) {
            throw new JobRetrievalServiceException(
                'Not authorised',
                JobRetrievalServiceException::CODE_NOT_AUTHORISED
            );
        }

        return $job;
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    private function isAuthorised(Job $job)
    {
        if ($job->getIsPublic()) {
            return true;
        }

        if ($job->getUser()->equals($this->user)) {
            return true;
        }

        if ($this->isUserWithinTeamThatOwnsjob($job)) {
            return true;
        }

        return false;
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    private function isUserWithinTeamThatOwnsjob(Job $job)
    {
        if ($this->isJobOwnedByTeamLeaderAndUserMemberOfSameTeam($job)) {
            return true;
        }

        if ($this->isJobOwnedByTeamMemberAndUserMemberOfSameTeam($job)) {
            return true;
        }

        if ($this->isJobOwnedByTeamMemberAndUserLeaderOfSameTeam($job)) {
            return true;
        }

        return false;
    }


    /**
     * @param Job $job
     *
     * @return bool
     */
    private function isJobOwnedByTeamLeaderAndUserMemberOfSameTeam(Job $job)
    {
        if (!$this->teamService->hasTeam($job->getUser())) {
            return false;
        }

        $team = $this->teamService->getForUser($job->getUser());

        return $this->teamService->getMemberService()->contains($team, $this->user);
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    private function isJobOwnedByTeamMemberAndUserMemberOfSameTeam(Job $job)
    {
        if (!$this->teamService->getMemberService()->belongsToTeam($job->getUser())) {
            return false;
        }

        $team = $this->teamService->getForUser($job->getUser());

        return $this->teamService->getMemberService()->contains($team, $this->user);
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    private function isJobOwnedByTeamMemberAndUserLeaderOfSameTeam(Job $job)
    {
        if (!$this->teamService->getMemberService()->belongsToTeam($job->getUser())) {
            return false;
        }

        $team = $this->teamService->getForUser($job->getUser());

        return $team->getLeader()->equals($this->user);
    }

    /**
     * @return bool
     */
    private function hasUser()
    {
        return $this->user instanceof User;
    }
}
