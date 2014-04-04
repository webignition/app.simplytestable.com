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
    const DEFAULT_URL_RETRIEVER_TOTAL_TIMEOUT = 30;
    
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
     * @var WebsiteSitemapFinder
     */
    private $sitemapFinder = null;
    
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
    private function find($canonicalUrl) {
        return $this->getEntityRepository()->findOneByCanonicalUrl($canonicalUrl);
    }    
    
    
    /**
     *
     * @param string $canonicalUrl
     * @return boolean
     */
    private function has($canonicalUrl) {      
        return !is_null($this->find($canonicalUrl));
    }
    
    
    /**
     *
     * @param string $canonicalUrl 
     * @return \SimplyTestable\ApiBundle\Entity\WebSite
     */
    private function create($canonicalUrl) {
        $website = new WebSite();
        $website->setCanonicalUrl($canonicalUrl);
        
        $this->persistAndFlush($website);
        return $website;
    }
    
    
    /**
     *
     * @param WebSite $website
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
     * @param \SimplyTestable\ApiBundle\Entity\WebSite $website
     * @param array $parameters
     * @return string[]
     */
    public function getUrls(WebSite $website, $parameters) {
        return $this->filterUrlsToWebsiteHost($website, $this->collectUrls($website, $parameters));
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
            if ($urlObject->hasHost() && $urlObject->getHost()->isEquivalentTo($websiteUrl->getHost(), array(
                'www'
            ))) {
                $filteredUrls[] = $url;
            }
        }
        
        return $filteredUrls;
    }
    
    
    private function collectUrls(WebSite $website, $parameters) {   
        $this->sitemapFinder = null;
        $this->websiteRssFeedFinder = null;
        
        $urlsFromSitemap = $this->getUrlsFromSitemap($website, $parameters);                
        if (count($urlsFromSitemap)) {
            return $urlsFromSitemap;
        }
        
        $urlsFromRssFeed = $this->getUrlsFromRssFeed($website, $parameters);        
        if (count($urlsFromRssFeed)) {
            return $urlsFromRssFeed;
        }
        
        $urlsFromAtomFeed = $this->getUrlsFromAtomFeed($website, $parameters);                
        if (count($urlsFromAtomFeed)) {
            return $urlsFromAtomFeed;
        }           
        
        return array();        
    }
    
    
    /**
     *
     * @param WebSite $website
     * @param array $parameters
     * @return array 
     */
    private function getUrlsFromSitemap(WebSite $website, $parameters) {               
        $sitemapFinder = $this->getSitemapFinder();        
        $sitemapFinder->getSitemapRetriever()->reset();
        $sitemapFinder->getConfiguration()->setRootUrl($website->getCanonicalUrl());        
        
        if (isset($parameters['softLimit'])) {
            $sitemapFinder->getUrlLimitListener()->setSoftLimit($parameters['softLimit']);
        }   
        
        $this->getHttpClientService()->prepareRequest($sitemapFinder->getConfiguration()->getBaseRequest(), $parameters);
        
        if (isset($parameters['cookies'])) {
            $sitemapFinder->getConfiguration()->setCookies($parameters['cookies']);
            $sitemapFinder->getSitemapRetriever()->getConfiguration()->setCookies($parameters['cookies']);
        }
        
        $sitemaps = $sitemapFinder->getSitemaps();
        
        if (count($sitemaps) === 0) {
            return array();
        }
        
        $urls = array();
        foreach ($sitemaps as $sitemap) {            
            /* @var $sitemap Sitemap */
            
            if (isset($parameters['softLimit']) && count($urls) < $parameters['softLimit']) {                
                $urls = array_merge($urls, $this->getUrlsFromSingleSitemap($sitemap, $parameters, count($urls)));                
            }

        }
        
        return $urls;
    }
    
    
    /**
     * @return WebsiteSitemapFinder
     */
    public function getSitemapFinder() {
        if (is_null($this->sitemapFinder)) {
            $this->sitemapFinder = new WebsiteSitemapFinder();
            $this->sitemapFinder->getConfiguration()->setBaseRequest($this->httpClientService->getRequest());           
            $this->sitemapFinder->getSitemapRetriever()->getConfiguration()->disableRetrieveChildSitemaps();
            
            if ($this->sitemapFinder->getSitemapRetriever()->getConfiguration()->getTotalTransferTimeout() == \webignition\WebsiteSitemapRetriever\Configuration\Configuration::DEFAULT_TOTAL_TRANSFER_TIMEOUT) {
                $this->sitemapFinder->getSitemapRetriever()->getConfiguration()->setTotalTransferTimeout(self::DEFAULT_URL_RETRIEVER_TOTAL_TIMEOUT);
            }
        }
        
        return $this->sitemapFinder;
    }
    
    
    /**
     * 
     * @param \webignition\WebResource\Sitemap\Sitemap $sitemap
     * @return array
     */
    private function getUrlsFromSingleSitemap(Sitemap $sitemap, $parameters, $count) {
        if (isset($parameters['softLimit']) && $count >= $parameters['softLimit']) {
            return array();
        }
        
        if (!$sitemap->isSitemap()) {
            return array();
        }
        
        if (!$sitemap->isIndex()) {
            return $sitemap->getUrls();
        }
        
        $urls = array();
        foreach  ($sitemap->getChildren() as $childSitemap) {
            if (isset($parameters['softLimit'])  && ($count + count($urls) >= $parameters['softLimit'])) {
                return $urls;
            }            
            
            /* @var $childSitemap Sitemap */
            if (is_null($childSitemap->getContent())) {                
                $sitemapRetriever = new \webignition\WebsiteSitemapRetriever\WebsiteSitemapRetriever();
                $sitemapRetriever->getConfiguration()->setBaseRequest($this->getHttpClientService()->getRequest());
                $sitemapRetriever->getConfiguration()->disableRetrieveChildSitemaps();
                $sitemapRetriever->retrieve($childSitemap);              
            }
            
            $childSitemapUrls = $this->getUrlsFromSingleSitemap($childSitemap, $parameters, $count + count($urls));            
            $urls = array_merge($urls, $childSitemapUrls);
        }
        
        return $urls;
    }
    

    /**
     *
     * @param WebSite $website
     * @return array 
     */    
    private function getUrlsFromRssFeed(WebSite $website, $parameters) {        
        $feedFinder = $this->getWebsiteRssFeedFinder($website, $parameters);
        $feedUrls = $feedFinder->getRssFeedUrls();               
        
        if (is_null($feedUrls)) {
            return array();
        }
        
        $urlsFromFeed = array();

        foreach ($feedUrls as $feedUrl) {
            $urlsFromFeed = array_merge($urlsFromFeed, $this->getUrlsFromNewsFeed($feedUrl, $parameters));
        }

        return is_null($urlsFromFeed) ? array() : $urlsFromFeed;
    }
    
    
    public function getWebsiteRssFeedFinder(WebSite $website, $parameters) {
        if (is_null($this->websiteRssFeedFinder)) {
            $this->websiteRssFeedFinder = new WebsiteRssFeedFinder();
            $this->websiteRssFeedFinder->getConfiguration()->setBaseRequest($this->httpClientService->getRequest());
            $this->websiteRssFeedFinder->getConfiguration()->setRootUrl($website->getCanonicalUrl());
            $this->websiteRssFeedFinder->getConfiguration()->getBaseRequest()->getClient()->setUserAgent('ST News Feed URL Retriever/0.1 (http://bit.ly/RlhKCL)');
            
            $this->getHttpClientService()->prepareRequest($this->websiteRssFeedFinder->getConfiguration()->getBaseRequest(), $parameters);
                                    
            if (isset($parameters['cookies'])) {
                $this->websiteRssFeedFinder->getConfiguration()->setCookies($parameters['cookies']);
            }            
        }
        
        return $this->websiteRssFeedFinder;
    }
    

    
    /**
     *
     * @param WebSite $website
     * @return array 
     */
    private function getUrlsFromAtomFeed(WebSite $website, $parameters) {
        $feedFinder = $this->getWebsiteRssFeedFinder($website, $parameters);    

        $feedUrls = $feedFinder->getAtomFeedUrls();                
        if (is_null($feedUrls)) {
            return array();
        }
        
        $urlsFromFeed = array();
        
        try {
            foreach ($feedUrls as $feedUrl) {
                $urlsFromFeed = array_merge($urlsFromFeed, $this->getUrlsFromNewsFeed($feedUrl, $parameters));
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
    private function getUrlsFromNewsFeed($feedUrl, $parameters) {
        try {
            $request = $this->getHttpClientService()->getRequest($feedUrl);
            
            $this->getHttpClientService()->prepareRequest($request, $parameters);
            
            $response = $request->send();
        } catch (\Guzzle\Http\Exception\RequestException $requestException) {            
            return array();
        } catch (\Guzzle\Common\Exception\InvalidArgumentException $e) {
            return array();
        }
      
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