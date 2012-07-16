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
    protected $entityManager;
    
    /**
     *
     * @var string
     */
    private $entityClassName = null;
    
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $entityManager 
     */
    public function __construct(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
    }
    
    
    /**
     *
     * @param string $entityClassName 
     */
    public function setEntityClassName($entityClassName) {
        $this->entityClassName = $entityClassName;
    }
    
    
    abstract public function fetch($identifier);    
    abstract public function persist(Object $entity);
}