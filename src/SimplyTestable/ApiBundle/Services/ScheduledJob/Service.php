<?php
namespace SimplyTestable\ApiBundle\Services\ScheduledJob;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use Cron\CronBundle\Entity\CronJob;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\EntityService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService as JobConfigurationService;
use Cron\CronBundle\Cron\Manager as CronManager;
use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobException;


class Service extends EntityService {

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
     * @var User
     */
    private $user;


    public function __construct(
        EntityManager $entityManager,
        JobConfigurationService $jobConfigurationService,
        TeamService $teamService,
        CronManager $cronManager
    ) {
        parent::__construct($entityManager);
        $this->jobConfigurationService = $jobConfigurationService;
        $this->teamService = $teamService;
        $this->cronManager = $cronManager;
    }

    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }


    /**
     * @param User $user
     */
    public function setUser(User $user) {
        $this->user = $user;
    }


    /**
     * @return bool
     */
    public function hasUser() {
        return !is_null($this->user);
    }


    public function create(JobConfiguration $jobConfiguration, $schedule = '* * * * *', $isRecurring = true) {
        if (!$jobConfiguration->hasUser()) {
            throw new ScheduledJobException(
                'User is not set',
                ScheduledJobException::CODE_USER_NOT_SET
            );
        }

        if ($this->getEntityRepository()->has($jobConfiguration, $schedule, $isRecurring)) {
            throw new ScheduledJobException(
                'Matching scheduled job exists',
                ScheduledJobException::CODE_MATCHING_SCHEDULED_JOB_EXISTS
            );
        }

        $this->user = $jobConfiguration->getUser();

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

        $this->getManager()->persist($scheduledJob);
        $this->getManager()->flush($scheduledJob);

        $scheduledJob->getCronJob()->setCommand('simplytestable:scheduledjob:enqueue ' . $scheduledJob->getId());
        $scheduledJob->getCronJob()->setName($jobConfiguration->getId().':'.$cronJob->getId());

        $this->cronManager->saveJob($cronJob);

        return $scheduledJob;
    }


    /**
     * @param $id
     * @return null|ScheduledJob
     * @throws \SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception
     */
    public function get($id) {
        if (!$this->hasUser()) {
            throw new ScheduledJobException(
                'User is not set',
                ScheduledJobException::CODE_USER_NOT_SET
            );
        }

        /* @var $scheduledJob ScheduledJob */
        $scheduledJob = $this->getEntityRepository()->find($id);
        if (is_null($scheduledJob)) {
            return null;
        }

        if ($scheduledJob->getJobConfiguration()->getUser()->equals($this->user)) {
            return $scheduledJob;
        }

        if (!$this->teamService->hasForUser($this->user)) {
            return null;
        }

        $people = $this->teamService->getPeopleForUser($this->user);

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
    public function getList() {
        if (!$this->hasUser()) {
            throw new ScheduledJobException(
                'User is not set',
                ScheduledJobException::CODE_USER_NOT_SET
            );
        }

        return $this->getEntityRepository()->getList(
            ($this->teamService->hasForUser($this->user) ? $this->teamService->getPeopleForUser($this->user) : [$this->user])
        );
    }


    /**
     * @param ScheduledJob $scheduledJob
     */
    public function delete(ScheduledJob $scheduledJob) {
        $this->getManager()->remove($scheduledJob->getCronJob());
        $this->getManager()->remove($scheduledJob);
        $this->getManager()->flush();
    }


    /**
     * @param ScheduledJob $scheduledJob
     * @param JobConfiguration $jobConfiguration
     * @param string $schedule
     * @param bool $isRecurring
     * @throws ScheduledJobException
     */
    public function update(ScheduledJob $scheduledJob, JobConfiguration $jobConfiguration = null, $schedule = null, $isRecurring = null) {
        $comparatorJobConfiguration = is_null($jobConfiguration) ? clone $scheduledJob->getJobConfiguration() : $jobConfiguration;
        $comparatorSchedule = is_null($schedule) ? $scheduledJob->getCronJob()->getSchedule() : $schedule;
        $comparatorIsRecurring = is_null($isRecurring) ? $scheduledJob->getIsRecurring() : $isRecurring;

        if (!is_null($jobConfiguration) && $scheduledJob->getJobConfiguration()->getId() == $jobConfiguration->getId()) {
            $jobConfiguration = null;
        }

        if (!is_null($schedule) && $scheduledJob->getCronJob()->getSchedule() == $schedule) {
            $schedule = null;
        }

        if (!is_null($isRecurring) && $scheduledJob->getIsRecurring() == $isRecurring) {
            $isRecurring = null;
        }

        if (is_null($jobConfiguration) && is_null($schedule) && is_null($isRecurring)) {
            return;
        }

        if (!is_null($comparatorJobConfiguration) && !is_null($comparatorSchedule) && !is_null($comparatorIsRecurring)) {
            if ($this->getEntityRepository()->has($comparatorJobConfiguration, $comparatorSchedule, $comparatorIsRecurring)) {
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
     *
     * @return \SimplyTestable\ApiBundle\Repository\ScheduledJob\Repository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    }


    /**
     * @throws ScheduledJobException
     */
    public function removeAll() {
        if (!$this->hasUser()) {
            throw new ScheduledJobException(
                'User is not set',
                ScheduledJobException::CODE_USER_NOT_SET
            );
        }

        if ($this->teamService->hasForUser($this->user)) {
            throw new ScheduledJobException(
                'Unable to remove all; user is in a team',
                ScheduledJobException::CODE_UNABLE_TO_PERFORM_AS_USER_IS_IN_A_TEAM
            );
        }

        $userScheduledJobs = $this->getEntityRepository()->getList([$this->user]);

        foreach ($userScheduledJobs as $userScheduledJob) {
            /* @var $userScheduledJob ScheduledJob */
            $this->getManager()->remove($userScheduledJob->getCronJob());
            $this->getManager()->remove($userScheduledJob);

            $this->getManager()->flush();
        }
    }
}