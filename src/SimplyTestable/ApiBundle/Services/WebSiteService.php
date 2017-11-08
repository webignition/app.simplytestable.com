<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\WebSite;
use webignition\NormalisedUrl\NormalisedUrl;

class WebSiteService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $websiteRepository;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->websiteRepository = $entityManager->getRepository(WebSite::class);
    }

    /**
     * @param string $canonicalUrl
     *
     * @return WebSite
     */
    public function fetch($canonicalUrl)
    {
        $normalisedUrl = (string)new NormalisedUrl($canonicalUrl);

        $website = $this->websiteRepository->findOneBy([
            'canonicalUrl' => $normalisedUrl,
        ]);

        if (empty($website)) {
            $website = new WebSite();
            $website->setCanonicalUrl($normalisedUrl);

            $this->entityManager->persist($website);
            $this->entityManager->flush();
        }

        return $website;
    }
}
