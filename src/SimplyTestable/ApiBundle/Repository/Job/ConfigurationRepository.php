<?php
namespace SimplyTestable\ApiBundle\Repository\Job;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;

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



}