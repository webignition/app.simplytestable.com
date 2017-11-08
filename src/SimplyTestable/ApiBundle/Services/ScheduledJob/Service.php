<?php
namespace SimplyTestable\ApiBundle\Services\ScheduledJob;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use Cron\CronBundle\Entity\CronJob;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Repository\ScheduledJobRepository;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService as JobConfigurationService;
use Cron\CronBundle\Cron\Manager as CronManager;
use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Service
{
    /**
     * @var JobConfigurationService
     */
    private $jobConfigurationService;

    /**
     * @var TeamService
     */
    private $teamService;

    /**
     * @var CronManager
     */
    private $cronManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ScheduledJobRepository
     */
    private $scheduledJobRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param JobConfigurationService $jobConfigurationService
     * @param TeamService $teamService
     * @param CronManager $cronManager
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        JobConfigurationService $jobConfigurationService,
        TeamService $teamService,
        CronManager $cronManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->entityManager = $entityManager;
        $this->jobConfigurationService = $jobConfigurationService;
        $this->teamService = $teamService;
        $this->cronManager = $cronManager;
        $this->tokenStorage = $tokenStorage;

        $this->scheduledJobRepository = $entityManager->getRepository(ScheduledJob::class);
    }

    /**
     * @param JobConfiguration $jobConfiguration
     * @param string $schedule
     * @param null $cronModifier
     * @param bool $isRecurring
     *
     * @return ScheduledJob
     * @throws ScheduledJobException
     */
    public function create(
        JobConfiguration $jobConfiguration,
        $schedule = '* * * * *',
        $cronModifier = null,
        $isRecurring = true
    ) {
        if ($this->scheduledJobRepository->has($jobConfiguration, $schedule, $cronModifier, $isRecurring)) {
            throw new ScheduledJobException(
                'Matching scheduled job exists',
                ScheduledJobException::CODE_MATCHING_SCHEDULED_JOB_EXISTS
            );
        }

        $cronJob = new CronJob();
        $cronJob->setCommand('');
        $cronJob->setDescription('');
        $cronJob->setEnabled(true);
        $cronJob->setName($jobConfiguration->getId().':'.md5(rand()));
        $cronJob->setSchedule($schedule);

        $this->cronManager->saveJob($cronJob);

        $scheduledJob = new ScheduledJob();
        $scheduledJob->setCronJob($cronJob);
        $scheduledJob->setJobConfiguration($jobConfiguration);
        $scheduledJob->setIsRecurring($isRecurring);
        $scheduledJob->setCronModifier($cronModifier);

        $this->entityManager->persist($scheduledJob);
        $this->entityManager->flush();

        $command = 'simplytestable:scheduledjob:enqueue ' . $scheduledJob->getId();

        $cronModifier = $scheduledJob->getCronModifier();

        if (!empty($cronModifier)) {
            $command .= ' #' . $cronModifier;
        }

        $scheduledJob->getCronJob()->setCommand($command);
        $scheduledJob->getCronJob()->setName($jobConfiguration->getId().':'.$cronJob->getId());

        $this->cronManager->saveJob($cronJob);

        return $scheduledJob;
    }

    /**
     * @param $id
     *
     * @return null|ScheduledJob
     */
    public function get($id)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        /* @var $scheduledJob ScheduledJob */
        $scheduledJob = $this->scheduledJobRepository->find($id);
        if (is_null($scheduledJob)) {
            return null;
        }

        if ($scheduledJob->getJobConfiguration()->getUser()->equals($user)) {
            return $scheduledJob;
        }

        if (!$this->teamService->hasForUser($user)) {
            return null;
        }

        $people = $this->teamService->getPeopleForUser($user);

        foreach ($people as $person) {
            if ($scheduledJob->getJobConfiguration()->getUser()->equals($person)) {
                return $scheduledJob;
            }
        }

        return null;
    }

    /**
     * @return ScheduledJob[]
     */
    public function getList()
    {
        $user = $this->tokenStorage->getToken()->getUser();

        return $this->scheduledJobRepository->getList(
            ($this->teamService->hasForUser($user)
                ? $this->teamService->getPeopleForUser($user)
                : [$user])
        );
    }

    /**
     * @param ScheduledJob $scheduledJob
     */
    public function delete(ScheduledJob $scheduledJob)
    {
        $this->entityManager->remove($scheduledJob->getCronJob());
        $this->entityManager->remove($scheduledJob);
        $this->entityManager->flush();
    }

    /**
     * @param ScheduledJob $scheduledJob
     * @param JobConfiguration $jobConfiguration
     * @param string $schedule
     * @param string $cronModifier
     * @param bool $isRecurring
     *
     * @throws ScheduledJobException
     */
    public function update(
        ScheduledJob $scheduledJob,
        JobConfiguration $jobConfiguration = null,
        $schedule = null,
        $cronModifier = null,
        $isRecurring = null
    ) {
        $comparatorJobConfiguration = is_null($jobConfiguration)
            ? clone $scheduledJob->getJobConfiguration()
            : $jobConfiguration;

        $comparatorSchedule = is_null($schedule) ? $scheduledJob->getCronJob()->getSchedule() : $schedule;
        $comparatorIsRecurring = is_null($isRecurring) ? $scheduledJob->getIsRecurring() : $isRecurring;

        $scheduledJobJobConfiguration = $scheduledJob->getJobConfiguration();

        if (!is_null($jobConfiguration) && $scheduledJobJobConfiguration->getId() == $jobConfiguration->getId()) {
            $jobConfiguration = null;
        }

        if (!is_null($schedule) && $scheduledJob->getCronJob()->getSchedule() == $schedule) {
            $schedule = null;
        }

        if (!is_null($cronModifier) && $scheduledJob->getCronModifier() == $cronModifier) {
            $cronModifier = null;
        }

        if (!is_null($isRecurring) && $scheduledJob->getIsRecurring() == $isRecurring) {
            $isRecurring = null;
        }

        if (is_null($jobConfiguration) && is_null($schedule) && is_null($isRecurring) && is_null($cronModifier)) {
            return;
        }

        $comparatorJobConfigurationIsNull = is_null($comparatorJobConfiguration);
        $comparatorScheduleIsNull = is_null($comparatorSchedule);
        $comparatorIsRecurringIsNull = is_null($comparatorIsRecurring);

        if (!$comparatorJobConfigurationIsNull && !$comparatorScheduleIsNull && !$comparatorIsRecurringIsNull) {
            $hasExisting = $this->scheduledJobRepository->has(
                $comparatorJobConfiguration,
                $comparatorSchedule,
                $cronModifier,
                $comparatorIsRecurring
            );

            if ($hasExisting) {
                throw new ScheduledJobException(
                    'Matching scheduled job exists',
                    ScheduledJobException::CODE_MATCHING_SCHEDULED_JOB_EXISTS
                );
            }
        }

        if (!is_null($schedule)) {
            $scheduledJob->getCronJob()->setSchedule($schedule);
            $this->entityManager->persist($scheduledJob->getCronJob());
        }

        if (!is_null($cronModifier)) {
            $scheduledJob->setCronModifier($cronModifier);
        }

        if (!is_null($jobConfiguration)) {
            $scheduledJob->setJobConfiguration($jobConfiguration);
        }

        if (!is_null($isRecurring)) {
            $scheduledJob->setIsRecurring($isRecurring);
        }

        $this->entityManager->persist($scheduledJob);
        $this->entityManager->flush();

        return;
    }

    /**
     * @throws ScheduledJobException
     */
    public function removeAll()
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if ($this->teamService->hasForUser($user)) {
            throw new ScheduledJobException(
                'Unable to remove all; user is in a team',
                ScheduledJobException::CODE_UNABLE_TO_PERFORM_AS_USER_IS_IN_A_TEAM
            );
        }

        $userScheduledJobs = $this->scheduledJobRepository->getList([$user]);

        foreach ($userScheduledJobs as $userScheduledJob) {
            /* @var $userScheduledJob ScheduledJob */
            $this->entityManager->remove($userScheduledJob->getCronJob());
            $this->entityManager->remove($userScheduledJob);

            $this->entityManager->flush();
        }
    }
}
