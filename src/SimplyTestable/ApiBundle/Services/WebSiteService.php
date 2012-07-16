<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\WebSite;
use webignition\NormalisedUrl\NormalisedUrl;

class WebSiteService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\WebSite';
    const IDENIFIER_FIELD = 'canonicalUrl';    
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    /**
     *
     * @return string
     */
    protected function getIdentifierField() {
        return self::IDENIFIER_FIELD;
    }    
    
    
    /**
     *
     * @param string $identifier
     * @return \SimplyTestable\ApiBundle\Entity\WebSite 
     */
    public function fetch($identifier) {
        $identifier = (string)new NormalisedUrl($identifier);        
        if (!$this->has($identifier)) {
            $this->create($identifier);
        }
        
        return $this->findByIdentifier($identifier);        
    }
    
    
    /**
     *
     * @param string $canonicalUrl 
     */
    public function create($canonicalUrl) {
        $website = new WebSite();
        $website->setCanonicalUrl($canonicalUrl);
        
        $this->persistAndFlush($website);
    }
}