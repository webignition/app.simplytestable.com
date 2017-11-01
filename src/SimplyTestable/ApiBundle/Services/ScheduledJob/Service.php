<?php
namespace SimplyTestable\ApiBundle\Services\ScheduledJob;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use Cron\CronBundle\Entity\CronJob;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Repository\ScheduledJob\Repository as ScheduledJobRepository;
use SimplyTestable\ApiBundle\Services\EntityService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService as JobConfigurationService;
use Cron\CronBundle\Cron\Manager as CronManager;
use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Service extends EntityService
{
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\ScheduledJob';

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
     * @param EntityManager $entityManager
     * @param JobConfigurationService $jobConfigurationService
     * @param TeamService $teamService
     * @param CronManager $cronManager
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        EntityManager $entityManager,
        JobConfigurationService $jobConfigurationService,
        TeamService $teamService,
        CronManager $cronManager,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct($entityManager);
        $this->jobConfigurationService = $jobConfigurationService;
        $this->teamService = $teamService;
        $this->cronManager = $cronManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return ScheduledJob::class;
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
        if ($this->getEntityRepository()->has($jobConfiguration, $schedule, $cronModifier, $isRecurring)) {
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

        $this->getManager()->persist($scheduledJob);
        $this->getManager()->flush($scheduledJob);

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
     * @throws ScheduledJobException
     */
    public function get($id)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        /* @var $scheduledJob ScheduledJob */
        $scheduledJob = $this->getEntityRepository()->find($id);
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
     * @throws ScheduledJobException
     */
    public function getList()
    {
        $user = $this->tokenStorage->getToken()->getUser();

        return $this->getEntityRepository()->getList(
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
        $this->getManager()->remove($scheduledJob->getCronJob());
        $this->getManager()->remove($scheduledJob);
        $this->getManager()->flush();
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
        $comparatorCronModifier = is_null($cronModifier) ? $scheduledJob->getCronModifier() : $cronModifier;
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
            $hasExisting = $this->getEntityRepository()->has(
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
            $this->getManager()->persist($scheduledJob->getCronJob());
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

        $this->getManager()->persist($scheduledJob);
        $this->getManager()->flush();

        return;
    }

    /**
     * @return ScheduledJobRepository
     */
    public function getEntityRepository()
    {
        return parent::getEntityRepository();
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

        $userScheduledJobs = $this->getEntityRepository()->getList([$user]);

        foreach ($userScheduledJobs as $userScheduledJob) {
            /* @var $userScheduledJob ScheduledJob */
            $this->getManager()->remove($userScheduledJob->getCronJob());
            $this->getManager()->remove($userScheduledJob);

            $this->getManager()->flush();
        }
    }
}
