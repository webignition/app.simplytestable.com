<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\WebSite;
use webignition\NormalisedUrl\NormalisedUrl;

class WebSiteService extends EntityService {
    
    /**
     *
     * @var \Doctrine\ORM\EntityManager 
     */
    //private $entityManager;
    
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $entityManager 
     */
//    public function __construct(EntityManager $entityManager) {
//        $this->entityManager = $entityManager;
//    }
    
    // Doctrine\ORM\EntityRepository
    
    
//    /**
//     *
//     * @return \Doctrine\ORM\EntityManager 
//     */
//    public function getEntityManager() {
//        return $this->entityManager;
//    }
    
    
    public function fetch($identifier) {
        $url = new NormalisedUrl($identifier);
        
        $website = $this->entityManager->getRepository('SimplyTestable\ApiBundle\Entity\WebSite')->findOneByCanonicalUrl((string)$url);
        var_dump($website, get_class($this->entityManager->getRepository('SimplyTestable\ApiBundle\Entity\WebSite')));
        exit();
        
    }
    
    public function persist(Object $entity) {
        
    }
    
    
//    public function persist(WebSite $website) {
//        
////            $state = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\State')->findOneByName($stateName);
////            $this->getEntityManager()->remove($state);
////            $this->getEntityManager()->flush();        
//        
//    }
    
    public function createWebSite() {
        
    }
}