<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\WebSite\Persist;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\WebSite;

class CanonicalUrlTest extends BaseSimplyTestableTestCase {

    public function testAscii() {
        $canonicalUrl = 'http://example.com/';

        $webSite = new WebSite();
        $webSite->setCanonicalUrl($canonicalUrl);

        $this->getWebSiteService()->getManager()->persist($webSite);
        $this->getWebSiteService()->getManager()->flush();
    }

    public function testUtf8() {
        $sourceUrl = 'http://example.com/ɸ';

        $webSite = new WebSite();
        $webSite->setCanonicalUrl($sourceUrl);

        $this->getWebSiteService()->getManager()->persist($webSite);
        $this->getWebSiteService()->getManager()->flush();

        $websiteId = $webSite->getId();

        $this->getWebSiteService()->getManager()->clear();

        $retrievedUrl = $this->getWebSiteService()->getEntityRepository()->find($websiteId)->getCanonicalUrl();

        /* Last character of the URL will be incorrect if the DB collation is not storing UTF8 correctly */
        $this->assertEquals(184, ord($retrievedUrl[strlen($retrievedUrl) - 1]));

    }


    public function testUrlGreaterThanVarcharLength() {
        $canonicalUrl = str_repeat('+', 1280);

        $webSite = new WebSite();
        $webSite->setCanonicalUrl($canonicalUrl);

        $this->getWebSiteService()->getManager()->persist($webSite);
        $this->getWebSiteService()->getManager()->flush();

        $preId = $webSite->getId();

        $this->getManager()->clear();

        $this->assertEquals($preId, $this->getWebSiteService()->fetch($canonicalUrl)->getId());
    }
}
