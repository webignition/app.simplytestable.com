<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\WebSite;
use webignition\NormalisedUrl\NormalisedUrl;
use webignition\WebsiteSitemapFinder\WebsiteSitemapFinder;
use webignition\WebsiteSitemapUrlRetriever\WebsiteSitemapUrlRetriever;

class WebSiteService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\WebSite';
    
    /**
     *
     * @var \webignition\Http\Client\Client
     */
    private $httpClient;
    
    /**
     *
     * @param EntityManager $entityManager
     * @param \webignition\Http\Client\Client $httpClient 
     */
    public function __construct(EntityManager $entityManager, \webignition\Http\Client\Client $httpClient) {
        parent::__construct($entityManager);
        $this->httpClient = $httpClient;
        $this->httpClient->redirectHandler()->enable();
    }
    

    /**
     *
     * @return \webignition\Http\Client\Client
     */
    public function getHttpClient() {
        return $this->httpClient;
    }    
    
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }

    
    /**
     * @param string $canonicalUrl
     * @return \SimplyTestable\ApiBundle\Entity\WebSite
     */
    public function fetch($canonicalUrl) {
        $normalisedUrl = (string)new NormalisedUrl($canonicalUrl);        
        if (!$this->has($normalisedUrl)) {
            $this->create($normalisedUrl);
        }

        return $this->find($normalisedUrl);
    }
    
    
    /**
     *
     * @param string $canonicalUrl
     * @return \SimplyTestable\ApiBundle\Entity\WebSite 
     */
    public function find($canonicalUrl) {
        return $this->getEntityRepository()->findOneByCanonicalUrl($canonicalUrl);
    }    
    
    
    /**
     *
     * @param string $canonicalUrl
     * @return boolean
     */
    public function has($canonicalUrl) {
        return !is_null($this->find($canonicalUrl));
    }
    
    
    /**
     *
     * @param string $canonicalUrl 
     * @return \SimplyTestable\ApiBundle\Entity\WebSite
     */
    public function create($canonicalUrl) {
        $website = new WebSite();
        $website->setCanonicalUrl($canonicalUrl);
        
        $this->persistAndFlush($website);
        return $website;
    }
    
    
    /**
     *
     * @param WebSite $job
     * @return WebSite
     */
    public function persistAndFlush(State $website) {
        $this->getEntityManager()->persist($website);
        $this->getEntityManager()->flush();
        return $website;
    }     
    
    
    /**
     * Get collection of URLs to be tested for a given website
     * 
     * @param WebSite $website
     * @return array
     */
    public function getUrls(WebSite $website) {
        $sitemapFinder = new WebsiteSitemapFinder();
        $sitemapFinder->setRootUrl($website->getCanonicalUrl());
        $sitemapFinder->setHttpClient($this->getHttpClient());
        
        $sitemapUrl = $sitemapFinder->getSitemapUrl();        
        if ($sitemapUrl === false) {
            return array();
        }
        
        $urlRetriever = new WebsiteSitemapUrlRetriever();
        $urlRetriever->setHttpClient($this->getHttpClient());
        $urlRetriever->setSitemapUrl($sitemapUrl);
        
        return $urlRetriever->getUrls();
    }
}