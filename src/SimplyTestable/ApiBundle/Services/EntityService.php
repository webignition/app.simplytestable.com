<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\WebSite;
use webignition\NormalisedUrl\NormalisedUrl;

abstract class EntityService {    
    
    /**
     *
     * @var \Doctrine\ORM\EntityManager 
     */
    private $entityManager;
    
    
    /**
     *
     * @var \Doctrine\ORM\EntityRepository
     */
    private $entityRepository;
    
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $entityManager 
     */
    public function __construct(EntityManager $entityManager) {
        $this->entityManager = $entityManager;      
    }    
  
    abstract protected function getEntityName();    
    
    /**
     *
     * @param string $entityName 
     */
    public function setEntityName($entityName) {
        $this->entityName = $entityName;
    }
    
    
    /**
     *
     * @return \Doctrine\ORM\EntityManager 
     */
    public function getEntityManager() {
        return $this->entityManager;
    }
    
    
    /**
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getEntityRepository() {
        if (is_null($this->entityRepository)) {
            $this->entityRepository = $this->entityManager->getRepository($this->getEntityName());
        }
        
        return $this->entityRepository;
    }   

}