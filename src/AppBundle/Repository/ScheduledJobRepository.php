<?php
namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use AppBundle\Entity\Job\Configuration as JobConfiguration;
use AppBundle\Entity\User;
use AppBundle\Entity\ScheduledJob;

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

        if (!is_null($cronModifier)) {
            $queryBuilder->andWhere('ScheduledJob.cronModifier = :CronModifier');
            $queryBuilder->setParameter('CronModifier', $cronModifier);
        }

        $queryBuilder->setParameter('JobConfiguration', $jobConfiguration);
        $queryBuilder->setParameter('IsRecurring', $isRecurring);
        $queryBuilder->setParameter('Schedule', $schedule);

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