<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;

class ScheduledJobRepository extends EntityRepository
{
    /**
     * @param JobConfiguration $jobConfiguration
     * @param $schedule
     * @param $cronModifier
     * @param $isRecurring
     *
     * @return bool
     */
    public function has(JobConfiguration $jobConfiguration, $schedule, $cronModifier, $isRecurring)
    {
        $queryBuilder = $this->createQueryBuilder('ScheduledJob');
        $queryBuilder->select('count(ScheduledJob.id)');
        $queryBuilder->join('ScheduledJob.cronJob', 'CronJob');

        $queryBuilder->where('ScheduledJob.jobConfiguration = :JobConfiguration');
        $queryBuilder->andWhere('ScheduledJob.isRecurring = :IsRecurring');
        $queryBuilder->andWhere('CronJob.schedule = :Schedule');
        $queryBuilder->andWhere('ScheduledJob.cronModifier = :CronModifier');

        $queryBuilder->setParameter('JobConfiguration', $jobConfiguration);
        $queryBuilder->setParameter('IsRecurring', $isRecurring);
        $queryBuilder->setParameter('Schedule', $schedule);
        $queryBuilder->setParameter('CronModifier', $cronModifier);

        $result = $queryBuilder->getQuery()->getResult();

        return $result[0][1] > 0;
    }

    /**
     * @param User[] $users
     *
     * @return ScheduledJob[]
     */
    public function getList($users = [])
    {
        $queryBuilder = $this->createQueryBuilder('ScheduledJob');
        $queryBuilder->select('ScheduledJob');
        $queryBuilder->join('ScheduledJob.jobConfiguration', 'JobConfiguration');
        $queryBuilder->where('JobConfiguration.user IN (:Users)');
        $queryBuilder->setParameter('Users', $users);

        return $queryBuilder->getQuery()->getResult();
    }
}
