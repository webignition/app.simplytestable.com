<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\WebSite;
use webignition\NormalisedUrl\NormalisedUrl;

class WebSiteService extends EntityService
{
    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager);
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return WebSite::class;
    }

    /**
     * @param string $canonicalUrl
     *
     * @return WebSite
     */
    public function fetch($canonicalUrl)
    {
        $normalisedUrl = (string)new NormalisedUrl($canonicalUrl);
        if (!$this->has($normalisedUrl)) {
            $this->create($normalisedUrl);
        }

        return $this->find($normalisedUrl);
    }

    /**
     * @param string $canonicalUrl
     *
     * @return WebSite
     */
    private function find($canonicalUrl)
    {
        return $this->getEntityRepository()->findOneByCanonicalUrl($canonicalUrl);
    }

    /**
     * @param string $canonicalUrl
     *
     * @return bool
     */
    private function has($canonicalUrl)
    {
        return !is_null($this->find($canonicalUrl));
    }

    /**
     * @param string $canonicalUrl
     *
     * @return WebSite
     */
    private function create($canonicalUrl)
    {
        $website = new WebSite();
        $website->setCanonicalUrl($canonicalUrl);

        $this->getManager()->persist($website);
        $this->getManager()->flush();

        return $website;
    }
}
