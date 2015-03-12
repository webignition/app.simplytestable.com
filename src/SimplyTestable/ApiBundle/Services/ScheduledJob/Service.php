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
     *
     * @return \SimplyTestable\ApiBundle\Repository\ScheduledJob\Repository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    }
}