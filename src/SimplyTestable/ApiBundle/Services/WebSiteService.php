<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\WebSite;
use webignition\NormalisedUrl\NormalisedUrl;
use webignition\WebsiteSitemapFinder\WebsiteSitemapFinder;
use webignition\WebsiteRssFeedFinder\WebsiteRssFeedFinder;
use webignition\WebResource\Sitemap\Sitemap;

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
        return $this->filterUrlsToWebsiteHost($website, $this->collectUrls($website));
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\WebSite $website
     * @param array $urls
     * @return array
     */
    private function filterUrlsToWebsiteHost(WebSite $website, $urls) {
        $websiteUrl = new \webignition\Url\Url($website->getCanonicalUrl());        
        $filteredUrls = array();
        
        foreach ($urls as $url) {
            $urlObject = new \webignition\Url\Url($url);
            if ($urlObject->getHost()->isEquivalentTo($websiteUrl->getHost(), array(
                'www'
            ))) {
                $filteredUrls[] = $url;
            }
        }
        
        return $filteredUrls;
    }
    
    
    private function collectUrls(WebSite $website) {
        $urlsFromSitemap = $this->getUrlsFromSitemap($website);
        if (count($urlsFromSitemap)) {
            return $urlsFromSitemap;
        }
        
        $urlsFromRssFeed = $this->getUrlsFromRssFeed($website);
        if (count($urlsFromRssFeed)) {
            return $urlsFromRssFeed;
        }
        
        $urlsFromAtomFeed = $this->getUrlsFromAtomFeed($website);        
        if (count($urlsFromAtomFeed)) {
            return $urlsFromAtomFeed;
        }        
        
        return array();        
    }
    
    
    /**
     *
     * @param WebSite $website
     * @return array 
     */
    private function getUrlsFromSitemap(WebSite $website) {
        $this->getHttpClient()->setUserAgent('SimplyTestable Sitemap Finder');
        
        $sitemapFinder = new WebsiteSitemapFinder();
        $sitemapFinder->setRootUrl($website->getCanonicalUrl());
        $sitemapFinder->setHttpClient($this->getHttpClient());
        $sitemaps = $sitemapFinder->getSitemaps();
        
        $this->getHttpClient()->clearUserAgent();
        
        if (count($sitemaps) === 0) {
            return array();
        }
        
        $urls = array();
        foreach ($sitemaps as $sitemap) {
            /* @var $sitemap Sitemap */
            $urls = array_merge($urls, $this->getUrlsFromSingleSitemap($sitemap));
        }
        
        return $urls;
    }
    
    
    /**
     * 
     * @param \webignition\WebResource\Sitemap\Sitemap $sitemap
     * @return array
     */
    private function getUrlsFromSingleSitemap(Sitemap $sitemap) {
        if (!$sitemap->isSitemap()) {
            return array();
        }
        
        if (!$sitemap->isIndex()) {
            return $sitemap->getUrls();
        }
        
        $urls = array();
        foreach  ($sitemap->getChildren() as $childSitemap) {
            /* @var $childSitemap Sitemap */
            $urls = array_merge($urls, $this->getUrlsFromSingleSitemap($childSitemap));
        }
        
        return $urls;
    }
    

    /**
     *
     * @param WebSite $website
     * @return array 
     */    
    private function getUrlsFromRssFeed(WebSite $website) {        
        $feedFinder = new WebsiteRssFeedFinder();
        $feedFinder->setRootUrl($website->getCanonicalUrl());        
        $feedFinder->setHttpClient($this->getHttpClient());
        
        $feedUrl = $feedFinder->getRssFeedUrl();       
        if (is_null($feedUrl)) {
            return array();
        }
        
        return $this->getUrlsFromNewsFeed($feedUrl);
    }

    
    /**
     *
     * @param WebSite $website
     * @return array 
     */
    private function getUrlsFromAtomFeed(WebSite $website) {        
        $feedFinder = new WebsiteRssFeedFinder();
        $feedFinder->setRootUrl($website->getCanonicalUrl());
        $feedFinder->setHttpClient($this->getHttpClient());
        
        $feedUrl = $feedFinder->getAtomFeedUrl();        
        if (is_null($feedUrl)) {
            return array();
        }
        
        return $this->getUrlsFromNewsFeed($feedUrl);
    }     
    
    
    /**
     *
     * @param string $feedUrl
     * @return array
     */
    private function getUrlsFromNewsFeed($feedUrl) {        
        $simplepie = new \SimplePie();
        $simplepie->set_feed_url($feedUrl);        
        $simplepie->enable_cache(false);
        @$simplepie->init();        
        
        $items = $simplepie->get_items();
        
        $urls = array();        
        foreach ($items as $item) {
            /* @var $item \SimplePie_Item */
            $url = new \webignition\NormalisedUrl\NormalisedUrl($item->get_permalink());
            if (!in_array((string)$url, $urls)) {
                $urls[] = (string)$url;
            }
        }
        
        return $urls;        
    }
    
}