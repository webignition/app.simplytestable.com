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
    
    abstract public function fetch($identifier);    
    abstract protected function getEntityName();
    abstract protected function getIdentifierField();    
    
    
    /**
     *
     * @param string $entityName 
     */
    public function setEntityName($entityName) {
        $this->entityName = $entityName;
    }
    
    
    /**
     *
     * @param string $identifierField 
     */
    public function setIdentifierField($identifierField) {
        $this->identifierField = $identifierField;
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
    
    
    /**
     * Find an entity by a given identifier.
     * 
     * An identifier is assumed unique; this will return a single entity only.
     * 
     * @param mixed $identifier
     * @return mixed
     */
    public function findByIdentifier($identifier) {
        $findByMethodName = 'findOneBy' . ucfirst($this->getIdentifierField());        
        return $this->getEntityRepository()->$findByMethodName($identifier);
    } 
    
    
    /**
     *
     * @param mixed $identifier
     * @return boolean
     */
    public function has($identifier) {
        return !is_null($this->findByIdentifier($identifier));
    }
    
    
    /**
     *
     * @param mixed $entity 
     */
    public function persistAndFlush($entity) {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }
    

}