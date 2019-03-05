<?php

namespace App\Services\Job;

use App\Entity\Job\Job;
use App\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;
use App\Repository\JobRepository;
use App\Services\Team\Service as TeamService;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RetrievalService
{
    private $teamService;
    private $jobRepository;
    private $tokenStorage;

    public function __construct(
        TeamService $teamService,
        TokenStorageInterface $tokenStorage,
        JobRepository $jobRepository
    ) {
        $this->teamService = $teamService;
        $this->tokenStorage = $tokenStorage;
        $this->jobRepository = $jobRepository;
    }

    /**
     * @param int $jobId
     * @throws JobRetrievalServiceException
     *
     * @return Job
     */
    public function retrieve($jobId)
    {
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

        $user = $this->tokenStorage->getToken()->getUser();

        if ($job->getUser()->equals($user)) {
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
        $user = $this->tokenStorage->getToken()->getUser();

        return $this->teamService->getMemberService()->contains($team, $user);
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
        $user = $this->tokenStorage->getToken()->getUser();

        return $this->teamService->getMemberService()->contains($team, $user);
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
        $user = $this->tokenStorage->getToken()->getUser();

        return $team->getLeader()->equals($user);
    }
}
