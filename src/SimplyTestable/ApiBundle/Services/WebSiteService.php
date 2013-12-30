<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\WebSite;
use webignition\NormalisedUrl\NormalisedUrl;
use webignition\WebsiteSitemapFinder\WebsiteSitemapFinder;
use webignition\WebsiteRssFeedFinder\WebsiteRssFeedFinder;
use webignition\WebResource\Sitemap\Sitemap;

class WebSiteService extends EntityService {
    
    const HTTP_AUTH_BASIC_NAME = 'Basic';
    const HTTP_AUTH_DIGEST_NAME = 'Digest';
    
    
    private $httpAuthNameToCurlAuthScheme = array(
        self::HTTP_AUTH_BASIC_NAME => CURLAUTH_BASIC,
        self::HTTP_AUTH_DIGEST_NAME => CURLAUTH_DIGEST
    );     
    
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
     * @param int $softLimit
     * @return array
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
        $sitemapFinder->getBaseRequest()->getClient()->setUserAgent('SimplyTestable Sitemap URL Retriever/0.1 (http://simplytestable.com/)');
        $sitemapFinder->getSitemapRetriever()->reset();
        $sitemapFinder->setRootUrl($website->getCanonicalUrl());
        
        if (isset($parameters['softLimit'])) {
            $sitemapFinder->getUrlLimitListener()->setSoftLimit($parameters['softLimit']);
        }        

        if (isset($parameters['http-auth-username']) || isset($parameters['http-auth-password'])) {
            $sitemapFinder->getBaseRequest()->setAuth(
                isset($parameters['http-auth-username']) ? isset($parameters['http-auth-username']) : '',
                isset($parameters['http-auth-password']) ? isset($parameters['http-auth-password']) : '',
                'any'
            );
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
            $this->sitemapFinder->setBaseRequest($this->httpClientService->get()->get());           
            
            if ($this->sitemapFinder->getSitemapRetriever()->getTotalTransferTimeout() == \webignition\WebsiteSitemapRetriever\WebsiteSitemapRetriever::DEFAULT_TOTAL_TRANSFER_TIMEOUT) {
                $this->sitemapFinder->getSitemapRetriever()->setTotalTransferTimeout(self::DEFAULT_URL_RETRIEVER_TOTAL_TIMEOUT);
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
                $sitemapRetriever->setBaseRequest($this->getHttpClientService()->get()->get());
                $sitemapRetriever->disableRetrieveChildSitemaps();
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
        $feedFinder = $this->getWebsiteRssFeedFinder($website);
        $feedFinder->getBaseRequest()->getClient()->setUserAgent('SimplyTestable RSS URL Retriever/0.1 (http://simplytestable.com/)');
        
        if (isset($parameters['http-auth-username']) || isset($parameters['http-auth-password'])) {
            $feedFinder->getBaseRequest()->setAuth(
                isset($parameters['http-auth-username']) ? isset($parameters['http-auth-username']) : '',
                isset($parameters['http-auth-password']) ? isset($parameters['http-auth-password']) : '',
                'any'
            );
        }       

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
    
    
    private function getWebsiteRssFeedFinder(WebSite $website) {
        if (is_null($this->websiteRssFeedFinder)) {
            $this->websiteRssFeedFinder = new WebsiteRssFeedFinder();
            $this->websiteRssFeedFinder->setBaseRequest($this->httpClientService->get()->get());
            $this->websiteRssFeedFinder->setRootUrl($website->getCanonicalUrl());
        }
        
        return $this->websiteRssFeedFinder;
    }

    
    /**
     *
     * @param WebSite $website
     * @return array 
     */
    private function getUrlsFromAtomFeed(WebSite $website, $parameters) {
        $feedFinder = $this->getWebsiteRssFeedFinder($website);
        $feedFinder->getBaseRequest()->getClient()->setUserAgent('SimplyTestable RSS URL Retriever/0.1 (http://simplytestable.com/)');      

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
            $response = $this->getNewsFeedResponse($request, $parameters);
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
    
    
    private function getNewsFeedResponse(\Guzzle\Http\Message\Request $request, $parameters, $failOnAuthenticationFailure = false) {
        try {
            return $request->send();     
        } catch (\Guzzle\Http\Exception\ClientErrorResponseException $clientErrorResponseException) {            
            /* @var $response \Guzzle\Http\Message\Response */
            $response = $clientErrorResponseException->getResponse();                        
            $authenticationScheme = $this->getWwwAuthenticateSchemeFromResponse($response);                        
            
            if (is_null($authenticationScheme) || $failOnAuthenticationFailure || !isset($parameters['http-auth-username']) || !isset($parameters['http-auth-username'])) {
                throw $clientErrorResponseException;
            }            

            $request->setAuth($parameters['http-auth-username'], $parameters['http-auth-password'], $this->getWwwAuthenticateSchemeFromResponse($response));
            return $this->getNewsFeedResponse($request, $parameters, true);
        }        
    }   
    
    
    /**
     * 
     * @param \Guzzle\Http\Message\Response $response
     * @return int|null
     */
    private function getWwwAuthenticateSchemeFromResponse(\Guzzle\Http\Message\Response $response) {
        if ($response->getStatusCode() !== 401) {
            return null;
        }
        
        if (!$response->hasHeader('www-authenticate')) {
            return null;
        }        
              
        $wwwAuthenticateHeaderValues = $response->getHeader('www-authenticate')->toArray();
        $firstLineParts = explode(' ', $wwwAuthenticateHeaderValues[0]);

        return (isset($this->httpAuthNameToCurlAuthScheme[$firstLineParts[0]])) ? $this->httpAuthNameToCurlAuthScheme[$firstLineParts[0]] : null;    
    }     
    
}