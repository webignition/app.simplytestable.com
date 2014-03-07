<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\WebSite\Persist;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\WebSite;

class CanonicalUrlTest extends BaseSimplyTestableTestCase {      

    public function testAscii() {
        $canonicalUrl = 'http://example.com/';        
        
        $webSite = new WebSite();
        $webSite->setCanonicalUrl($canonicalUrl);
        
        $this->getWebSiteService()->getEntityManager()->persist($webSite);
        $this->getWebSiteService()->getEntityManager()->flush();
    }   
    
    public function testUtf8() {        
        $sourceUrl = 'http://example.com/ɸ';        
        
        $webSite = new WebSite();
        $webSite->setCanonicalUrl($sourceUrl);        
        
        $this->getWebSiteService()->getEntityManager()->persist($webSite);
        $this->getWebSiteService()->getEntityManager()->flush();
        
        $websiteId = $webSite->getId();
        
        $this->getWebSiteService()->getEntityManager()->clear();
        
        $retrievedUrl = $this->getWebSiteService()->getEntityRepository()->find($websiteId)->getCanonicalUrl();
        
        /* Last character of the URL will be incorrect if the DB collation is not storing UTF8 correctly */
        $this->assertEquals(184, ord($retrievedUrl[strlen($retrievedUrl) - 1]));

    }      
}
