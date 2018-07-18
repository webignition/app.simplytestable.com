<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Entity\WebSite;
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
    public function get($canonicalUrl)
    {
        $normalisedUrlObject = new NormalisedUrl($canonicalUrl);

        $normalisedUrl = in_array($normalisedUrlObject->getScheme(), ['http', 'https'])
            ? (string)$normalisedUrlObject
            : $canonicalUrl;

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
