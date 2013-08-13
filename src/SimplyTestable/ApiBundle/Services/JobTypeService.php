<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Job\Type;

class JobTypeService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Job\Type';   
    const DEFAULT_TYPE_ID = 1;
    const FULL_SITE_NAME = 'full site';
    const SINGLE_URL_NAME = 'single url';
    const CRAWL_NAME = 'crawl';
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    /**
     * 
     * @return Type
     */
    public function getDefaultType() {
        return $this->getEntityRepository()->find(self::DEFAULT_TYPE_ID);
    }
    
    
    /**
     * 
     * @param string $name
     * @return Type
     */
    public function getByName($name) {
        return $this->getEntityRepository()->findOneBy(array(
            'name' => $name
        ));
    }
    
    
    /**
     * 
     * @param string $name
     * @return boolean
     */
    public function has($name) {
        return !is_null($this->getByName($name));
    }
    
    
    /**
     * 
     * @return Type
     */
    public function getFullSiteType() {
        return $this->getByName(self::FULL_SITE_NAME);
    }
    
    /**
     * 
     * @return Type
     */
    public function getSingleUrlType() {
        return $this->getByName(self::SINGLE_URL_NAME);
    }      
    
    
    /**
     * 
     * @return Type
     */
    public function getCrawlType() {
        return $this->getByName(self::CRAWL_NAME);
    }    
    
}