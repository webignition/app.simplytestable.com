<?php
namespace SimplyTestable\ApiBundle\Repository\Job;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class ConfigurationRepository extends EntityRepository {

    public function getCountByProperties(User $user, WebSite $website, JobType $type, $parameters = '') {
        $queryBuilder = $this->createQueryBuilder('JobConfiguration');
        $queryBuilder->select('COUNT(JobConfiguration.id)');
        $queryBuilder->where(
            'JobConfiguration.user = :User AND
             JobConfiguration.website = :WebSite AND
             JobConfiguration.type = :JobType AND
             JobConfiguration.parameters = :Parameters'
        );

        $queryBuilder->setParameter('User', $user);
        $queryBuilder->setParameter('WebSite', $website);
        $queryBuilder->setParameter('JobType', $type);
        $queryBuilder->setParameter('Parameters', $parameters);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }

    /**
     * @param WebSite $website
     * @param JobType $type
     * @param string $parameters
     * @param array $users
     * @return JobConfiguration[]
     */
    public function findByWebsiteAndTypeAndParametersAndUsers(WebSite $website, JobType $type, $parameters = '', $users = []) {
        $queryBuilder = $this->createQueryBuilder('JobConfiguration');
        $queryBuilder->select('JobConfiguration');
        $queryBuilder->where(
            'JobConfiguration.user IN (:Users) AND
             JobConfiguration.website = :WebSite AND
             JobConfiguration.type = :JobType AND
             JobConfiguration.parameters = :Parameters'
        );

        $queryBuilder->setParameter('Users', $users);
        $queryBuilder->setParameter('WebSite', $website);
        $queryBuilder->setParameter('JobType', $type);
        $queryBuilder->setParameter('Parameters', $parameters);

        return $queryBuilder->getQuery()->getResult();
    }


    /**
     * @param User[] $users
     * @return JobConfiguration[]
     */
    public function findByUsers($users = []) {
        $queryBuilder = $this->createQueryBuilder('JobConfiguration');
        $queryBuilder->select('JobConfiguration');
        $queryBuilder->where('JobConfiguration.user IN (:Users)');

        $queryBuilder->setParameter('Users', $users);

        return $queryBuilder->getQuery()->getResult();
    }



}