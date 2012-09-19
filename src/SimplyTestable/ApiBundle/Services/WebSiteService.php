<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\WebSite;
use webignition\NormalisedUrl\NormalisedUrl;
use webignition\WebsiteSitemapFinder\WebsiteSitemapFinder;
use webignition\WebsiteSitemapUrlRetriever\WebsiteSitemapUrlRetriever;
use webignition\WebsiteRssFeedFinder\WebsiteRssFeedFinder;

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
    public function persistAndFlush(WebSite $website) {
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
        $urlsFromSitemap = $this->getUrlsFromSitemap($website);
        if (count($urlsFromSitemap)) {
            return $urlsFromSitemap;
        }        
        
        $urlsFromRssFeed = $this->getUrlsFromRssFeed($website);
        if (count($urlsFromRssFeed)) {
            return $urlsFromRssFeed;
        }
        
        return array();
    }
    
    private function getUrlsFromSitemap(WebSite $website) {
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
    
    
    private function getUrlsFromRssFeed(WebSite $website) {        
        $rssFeedFinder = new WebsiteRssFeedFinder();
        $rssFeedFinder->setRootUrl($website->getCanonicalUrl());        
        
        $rssFeedUrl = $rssFeedFinder->getRssFeedUrl();        
        if (is_null($rssFeedUrl)) {
            return array();
        }
        
        $urls = array();
        
        $simplepie = new \SimplePie();
        $simplepie->set_feed_url($rssFeedUrl);
        $simplepie->enable_cache(false);
        $simplepie->init();        
        
        $items = $simplepie->get_items();
        foreach ($items as $item) {
            /* @var $item \SimplePie_Item */
            $urls[] = $item->get_permalink();
        }
        
        return $urls;
    }
    
}