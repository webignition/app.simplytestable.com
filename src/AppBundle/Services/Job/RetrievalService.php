<?php
namespace AppBundle\Services\Job;

use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\Job\Job;
use AppBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;
use AppBundle\Repository\JobRepository;
use AppBundle\Services\Team\Service as TeamService;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RetrievalService
{
    /**
     * @var TeamService
     */
    private $teamService;

    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TeamService $teamService
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TeamService $teamService,
        TokenStorageInterface $tokenStorage
    ) {
        $this->teamService = $teamService;
        $this->tokenStorage = $tokenStorage;

        $this->jobRepository = $entityManager->getRepository(Job::class);
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
