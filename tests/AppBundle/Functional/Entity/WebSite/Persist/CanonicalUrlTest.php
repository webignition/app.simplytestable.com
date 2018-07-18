<?php

namespace Tests\AppBundle\Functional\Entity\WebSite\Persist;

use Doctrine\ORM\EntityManagerInterface;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use AppBundle\Entity\WebSite;

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

        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');
    }


    public function testAscii()
    {
        $canonicalUrl = 'http://example.com/';

        $webSite = new WebSite();
        $webSite->setCanonicalUrl($canonicalUrl);

        $this->entityManager->persist($webSite);
        $this->entityManager->flush();

        $websiteId = $webSite->getId();

        $this->entityManager->close();

        $websiteRepository = $this->entityManager->getRepository(WebSite::class);

        $retrievedWebSite = $websiteRepository->find($websiteId);

        $this->assertSame($webSite->getId(), $retrievedWebSite->getId());
    }

    public function testUtf8()
    {
        $websiteRepository = $this->entityManager->getRepository(WebSite::class);

        $sourceUrl = 'http://example.com/ɸ';

        $webSite = new WebSite();
        $webSite->setCanonicalUrl($sourceUrl);

        $this->entityManager->persist($webSite);
        $this->entityManager->flush();

        $websiteId = $webSite->getId();

        $this->entityManager->clear();

        $retrievedUrl = $websiteRepository->find($websiteId)->getCanonicalUrl();

        /* Last character of the URL will be incorrect if the DB collation is not storing UTF8 correctly */
        $this->assertEquals(184, ord($retrievedUrl[strlen($retrievedUrl) - 1]));
    }

    public function testUrlGreaterThanVarcharLength()
    {
        $websiteRepository = $this->entityManager->getRepository(WebSite::class);

        $canonicalUrl = str_repeat('+', 1280);

        $webSite = new WebSite();
        $webSite->setCanonicalUrl($canonicalUrl);

        $this->entityManager->persist($webSite);
        $this->entityManager->flush();

        $preId = $webSite->getId();

        $this->entityManager->clear();

        $this->assertEquals($preId, $websiteRepository->find($preId)->getId());
    }
}
