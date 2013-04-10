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
     * @var \SimplyTestable\ApiBundle\Services\HttpClientService
     */
    private $httpClientService;
    
    
    /**
     *
     * @var \webignition\WebsiteRssFeedFinder\WebsiteRssFeedFinder
     */
    private $websiteRssFeedFinder;
    
    /**
     *
     * @param EntityManager $entityManager
     * @param \SimplyTestable\ApiBundle\Services\HttpClientService $httpClientService
     */
    public function __construct(
            EntityManager $entityManager,
            \SimplyTestable\ApiBundle\Services\HttpClientService $httpClientService) {
        parent::__construct($entityManager);
        $this->httpClientService = $httpClientService; 
    }
    

    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\HttpClientService
     */
    public function getHttpClientService() {        
        return $this->httpClientService;
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
        $this->websiteRssFeedFinder = null;
        
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
        $this->getHttpClientService()->get()->setUserAgent('SimplyTestable Sitemap URL Retriever/0.1 (http://simplytestable.com/)');
        
        $sitemapFinder = new WebsiteSitemapFinder();
        $sitemapFinder->setRootUrl($website->getCanonicalUrl());
        $sitemapFinder->setHttpClient($this->httpClientService->get());
        $sitemaps = $sitemapFinder->getSitemaps();
        
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
        $feedFinder = $this->getWebsiteRssFeedFinder($website);
        $feedFinder->getHttpClient()->setUserAgent('SimplyTestable RSS URL Retriever/0.1 (http://simplytestable.com/)');

        $feedUrls = $feedFinder->getRssFeedUrls();       
        if (is_null($feedUrls)) {
            return array();
        }

        $this->getHttpClient()->clearUserAgent();
        $urlsFromFeed = array();

        foreach ($feedUrls as $feedUrl) {
            $urlsFromFeed = array_merge($urlsFromFeed, $this->getUrlsFromNewsFeed($feedUrl));
        }

        return is_null($urlsFromFeed) ? array() : $urlsFromFeed;
    }
    
    
    private function getWebsiteRssFeedFinder(WebSite $website) {
        if (is_null($this->websiteRssFeedFinder)) {
            $this->websiteRssFeedFinder = new WebsiteRssFeedFinder();
            $this->websiteRssFeedFinder->setRootUrl($website->getCanonicalUrl());
            $this->websiteRssFeedFinder->setHttpClient($this->httpClientService->get());
        }
        
        return $this->websiteRssFeedFinder;
    }

    
    /**
     *
     * @param WebSite $website
     * @return array 
     */
    private function getUrlsFromAtomFeed(WebSite $website) {
        $feedFinder = $this->getWebsiteRssFeedFinder($website);
        $feedFinder->getHttpClient()->setUserAgent('SimplyTestable RSS URL Retriever/0.1 (http://simplytestable.com/)');      

        $feedUrls = $feedFinder->getAtomFeedUrls();                
        if (is_null($feedUrls)) {
            return array();
        }

        $urlsFromFeed = array();
        
        try {
            foreach ($feedUrls as $feedUrl) {
                $urlsFromFeed = array_merge($urlsFromFeed, $this->getUrlsFromNewsFeed($feedUrl));
            }            
        } catch (\Exception $e) {
            var_dump($e);
            exit();
        }
            
        return is_null($urlsFromFeed) ? array() : $urlsFromFeed;
    }
    
    
    /**
     *
     * @param string $feedUrl
     * @return array
     */
    private function getUrlsFromNewsFeed($feedUrl) {                
        $request = $this->getHttpClientService()->getRequest($feedUrl);
        $response = $request->send();
      
        $simplepie = new \SimplePie();
        $simplepie->set_raw_data($response->getBody(true));
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