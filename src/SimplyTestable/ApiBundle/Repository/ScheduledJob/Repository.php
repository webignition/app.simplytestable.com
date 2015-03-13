<?php
namespace SimplyTestable\ApiBundle\Repository\ScheduledJob;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;

class Repository extends EntityRepository {


    /**
     * @param JobConfiguration $jobConfiguration
     * @param $schedule
     * @param $isRecurring
     * @return bool
     */
    public function has(JobConfiguration $jobConfiguration, $schedule, $isRecurring) {
        $queryBuilder = $this->createQueryBuilder('ScheduledJob');
        $queryBuilder->select('count(ScheduledJob.id)');
        $queryBuilder->join('ScheduledJob.cronJob', 'CronJob');
        $queryBuilder->where('
        ScheduledJob.jobConfiguration = :JobConfiguration AND
        ScheduledJob.isRecurring = :IsRecurring AND
        CronJob.schedule = :Schedule
        ');
        $queryBuilder->setParameter('JobConfiguration', $jobConfiguration);
        $queryBuilder->setParameter('IsRecurring', $isRecurring);
        $queryBuilder->setParameter('Schedule', $schedule);


        $result = $queryBuilder->getQuery()->getResult();
        return $result[0][1] > 0;
    }


    /**
     * @param User[] $users
     * @return ScheduledJob[]
     */
    public function getList($users = []) {
        $queryBuilder = $this->createQueryBuilder('ScheduledJob');
        $queryBuilder->select('ScheduledJob');
        $queryBuilder->join('ScheduledJob.jobConfiguration', 'JobConfiguration');
        $queryBuilder->where('JobConfiguration.user IN (:Users)');
        $queryBuilder->setParameter('Users', $users);

        return $queryBuilder->getQuery()->getResult();
    }

}