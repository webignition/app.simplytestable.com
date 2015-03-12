<?php
namespace SimplyTestable\ApiBundle\Repository\ScheduledJob;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class Repository extends EntityRepository {

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
}