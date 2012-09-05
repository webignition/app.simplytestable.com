<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class JobRepository extends EntityRepository
{
    
    /**
     * 
     * @param $limit int
     * @return array
     */
    public function findAllOrderedByIdDesc($limit = null)
    {  
        $query = $this->getEntityManager()->createQuery('SELECT j FROM SimplyTestableApiBundle:Job\Job j ORDER BY j.id DESC');
        
        if (!is_null($limit)) {
            $query->setMaxResults($limit);
        }
        
        return $query->getResult();
    }
}