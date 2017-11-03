<?php

namespace Tests\ApiBundle\Functional\Entity\WebSite\Persist;

use Doctrine\ORM\EntityManagerInterface;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\WebSite;

class CanonicalUrlTest extends AbstractBaseTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');
    }


    public function testAscii()
    {
        $canonicalUrl = 'http://example.com/';

        $webSite = new WebSite();
        $webSite->setCanonicalUrl($canonicalUrl);

        $this->entityManager->persist($webSite);
        $this->entityManager->flush();
    }

    public function testUtf8()
    {
        $sourceUrl = 'http://example.com/ɸ';

        $webSite = new WebSite();
        $webSite->setCanonicalUrl($sourceUrl);

        $this->entityManager->persist($webSite);
        $this->entityManager->flush();

        $websiteId = $webSite->getId();

        $this->entityManager->clear();

        $websiteRepository = $this->entityManager->getRepository(WebSite::class);

        $retrievedUrl = $websiteRepository->find($websiteId)->getCanonicalUrl();

        /* Last character of the URL will be incorrect if the DB collation is not storing UTF8 correctly */
        $this->assertEquals(184, ord($retrievedUrl[strlen($retrievedUrl) - 1]));
    }

    public function testUrlGreaterThanVarcharLength()
    {
        $canonicalUrl = str_repeat('+', 1280);

        $webSite = new WebSite();
        $webSite->setCanonicalUrl($canonicalUrl);

        $this->entityManager->persist($webSite);
        $this->entityManager->flush();

        $preId = $webSite->getId();

        $this->entityManager->clear();

        $websiteRepository = $this->entityManager->getRepository(WebSite::class);

        $this->assertEquals($preId, $websiteRepository->find($preId)->getId());
    }
}
