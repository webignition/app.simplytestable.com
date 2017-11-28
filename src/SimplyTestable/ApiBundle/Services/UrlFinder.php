<?php

namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\WebSite;
use webignition\Url\Url;
use webignition\WebResource\Service\Service as WebResourceService;
use webignition\WebResource\Sitemap\Factory as SitemapFactory;
use webignition\WebResource\Sitemap\Sitemap;
use webignition\WebsiteRssFeedFinder\Configuration as WebsiteRssFeedFinderConfiguration;
use webignition\WebsiteRssFeedFinder\WebsiteRssFeedFinder;
use webignition\WebsiteSitemapFinder\Configuration as WebsiteSitemapFinderConfiguration;
use webignition\WebsiteSitemapFinder\WebsiteSitemapFinder;

class UrlFinder
{
    const DEFAULT_SITEMAP_RETRIEVER_TOTAL_TRANSFER_TIMEOUT = 30;
    const PARAMETER_KEY_COOKIES = 'cookies';

    const FEED_FINDER_USER_AGENT = 'ST News Feed URL Retriever/0.1 (http://bit.ly/RlhKCL)';
    const SITEMAP_RETRIEVER_USER_AGENT = 'ST Sitemap Retriever/0.1 (http://bit.ly/RlhKCL)';
    const SITEMAP_FINDER_USER_AGENT = 'ST Sitemap Finder/0.1 (http://bit.ly/RlhKCL)';

    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @var WebResourceService
     */
    private $webResourceService;

    /**
     * @var SitemapFactory
     */
    private $sitemapFactory;

    /**
     * @var int|float
     */
    private $sitemapRetrieverTotalTransferTimeout;

    /**
     * @var float
     */
    private $sitemapRetrieverTotalTransferTime;

    /**
     * @var WebsiteRssFeedFinder
     */
    private $websiteRssFeedFinder;

    /**
     * @param HttpClientService $httpClientService
     * @param WebResourceService $webResourceService
     * @param SitemapFactory $sitemapFactory
     * @param float|null $sitemapRetrieverTotalTransferTimeout
     */
    public function __construct(
        HttpClientService $httpClientService,
        WebResourceService $webResourceService,
        SitemapFactory $sitemapFactory,
        $sitemapRetrieverTotalTransferTimeout = null
    ) {
        $this->httpClientService = $httpClientService;
        $this->webResourceService = $webResourceService;
        $this->sitemapFactory = $sitemapFactory;

        $this->sitemapRetrieverTotalTransferTimeout = (empty($sitemapRetrieverTotalTransferTimeout))
            ? self::DEFAULT_SITEMAP_RETRIEVER_TOTAL_TRANSFER_TIMEOUT
            : $sitemapRetrieverTotalTransferTimeout;
    }

    /**
     * Get collection of URLs to be tested for a given website
     *
     * @param WebSite $website
     * @param int $softLimit
     * @param array $parameters
     *
     * @return string[]
     *
     */
    public function getUrls(WebSite $website, $softLimit, $parameters = [])
    {
        $this->sitemapRetrieverTotalTransferTime = 0;

        return $this->filterUrlsToWebsiteHost(
            $website,
            $this->collectUrls($website, $softLimit, $parameters)
        );
    }

    /**
     *
     * @param WebSite $website
     * @param string[] $urls
     *
     * @return string[]
     */
    private function filterUrlsToWebsiteHost(WebSite $website, $urls)
    {
        $websiteUrl = new Url($website->getCanonicalUrl());
        $filteredUrls = array();

        foreach ($urls as $url) {
            $urlObject = new Url($url);
            if ($urlObject->hasHost() && $urlObject->getHost()->isEquivalentTo($websiteUrl->getHost(), array(
                    'www'
                ))) {
                $filteredUrls[] = $url;
            }
        }

        return $filteredUrls;
    }

    /**
     * @param WebSite $website
     * @param int $softLimit
     * @param array $parameters
     *
     * @return string[]
     */
    private function collectUrls(WebSite $website, $softLimit, $parameters)
    {
        $urlsFromSitemap = $this->getUrlsFromSitemap($website, $softLimit, $parameters);

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
     * @param WebSite $website
     * @param int $softLimit
     * @param array $parameters
     *
     * @return string[]
     */
    private function getUrlsFromSitemap(WebSite $website, $softLimit, $parameters)
    {
        $this->httpClientService->setCookiesFromParameters($parameters);
        $this->httpClientService->setBasicHttpAuthenticationFromParameters($parameters);
        $this->httpClientService->setUserAgent(self::SITEMAP_FINDER_USER_AGENT);

        $configuration = new WebsiteSitemapFinderConfiguration([
            WebsiteSitemapFinderConfiguration::KEY_ROOT_URL => $website->getCanonicalUrl(),
            WebsiteSitemapFinderConfiguration::KEY_HTTP_CLIENT => $this->httpClientService->get(),
        ]);

        $sitemapFinder = new WebsiteSitemapFinder($configuration);

        $sitemapUrls = $sitemapFinder->findSitemapUrls();

        $this->httpClientService->clearBasicHttpAuthorization();
        $this->httpClientService->clearCookies();
        $this->httpClientService->resetUserAgent();

        $sitemaps = [];

        $this->httpClientService->setUserAgent(self::SITEMAP_RETRIEVER_USER_AGENT);

        foreach ($sitemapUrls as $sitemapUrl) {
            $sitemap = $this->retrieveSitemap($sitemapUrl, $parameters);

            if (!empty($sitemap)) {
                $sitemaps[] = $sitemap;
            }
        }

        if (empty($sitemaps)) {
            return [];
        }

        $urls = array();
        foreach ($sitemaps as $sitemap) {
            if (count($urls) < $softLimit) {
                $urls = array_merge(
                    $urls,
                    $this->getUrlsFromSingleSitemap($sitemap, $parameters, $softLimit, count($urls))
                );
            }
        }

        return $urls;
    }

    /**
     * @param string $url
     * @param array $parameters
     * @return null|Sitemap
     */
    private function retrieveSitemap($url, $parameters)
    {
        $this->httpClientService->setCookiesFromParameters($parameters);
        $this->httpClientService->setBasicHttpAuthenticationFromParameters($parameters);
        $this->httpClientService->setUserAgent(self::SITEMAP_RETRIEVER_USER_AGENT);

        $sitemap = null;

        try {
            $request = $this->httpClientService->getRequest($url);
            $sitemapResource = $this->webResourceService->get($request);
            $sitemap = $this->sitemapFactory->create($sitemapResource->getHttpResponse());
        } catch (\Exception $exception) {
            // Intentionally swallow all exceptions
        }

        $this->httpClientService->clearBasicHttpAuthorization();
        $this->httpClientService->clearCookies();
        $this->httpClientService->resetUserAgent();

        return $sitemap;
    }

    /**
     * @param Sitemap $sitemap
     * @param array $parameters
     * @param int $softLimit
     * @param int $count
     * @return string[]
     */
    private function getUrlsFromSingleSitemap(Sitemap $sitemap, $parameters, $softLimit, $count)
    {
        if (!$sitemap->isIndex()) {
            return $sitemap->getUrls();
        }

        $childSitemapUrls = $sitemap->getUrls();

        $totalTransferTime = 0;
        $totalTransferTimeExceeded = false;

        $urls = [];

        foreach ($childSitemapUrls as $childSitemapUrl) {
            if ($totalTransferTimeExceeded) {
                continue;
            }

            if (!is_null($softLimit) && ($count + count($urls) >= $softLimit)) {
                continue;
            }

            $timeBeforeTransfer = microtime(true);

            $childSitemap = $this->retrieveSitemap($childSitemapUrl, $parameters);

            $transferTime = microtime(true) - $timeBeforeTransfer;
            $totalTransferTime += $transferTime;
            $totalTransferTimeExceeded = $totalTransferTime > $this->sitemapRetrieverTotalTransferTimeout;

            $urls = array_merge($urls, $childSitemap->getUrls());
        }

        return $urls;
    }

    /**
     * @param WebSite $website
     * @param array $parameters
     *
     * @return string[]
     */
    private function getUrlsFromRssFeed(WebSite $website, $parameters)
    {
        $this->httpClientService->setUserAgent(self::FEED_FINDER_USER_AGENT);
        $this->httpClientService->setCookiesFromParameters($parameters);
        $this->httpClientService->setBasicHttpAuthenticationFromParameters($parameters);

        $feedFinder = $this->getWebsiteRssFeedFinder($website);
        $feedUrls = $feedFinder->getRssFeedUrls();

        $this->httpClientService->resetUserAgent();
        $this->httpClientService->clearCookies();
        $this->httpClientService->clearBasicHttpAuthorization();

        if (empty($feedUrls)) {
            return [];
        }

        return $this->getUrlsFromNewsFeeds($feedUrls, $parameters);
    }

    /**
     * @param WebSite $website
     * @param array $parameters
     *
     * @return string[]
     */
    private function getUrlsFromAtomFeed(WebSite $website, $parameters)
    {
        $this->httpClientService->setUserAgent(self::FEED_FINDER_USER_AGENT);
        $this->httpClientService->setCookiesFromParameters($parameters);
        $this->httpClientService->setBasicHttpAuthenticationFromParameters($parameters);

        $feedFinder = $this->getWebsiteRssFeedFinder($website);
        $feedUrls = $feedFinder->getAtomFeedUrls();

        $this->httpClientService->resetUserAgent();
        $this->httpClientService->clearCookies();
        $this->httpClientService->clearBasicHttpAuthorization();

        if (empty($feedUrls)) {
            return [];
        }

        return $this->getUrlsFromNewsFeeds($feedUrls, $parameters);
    }

    /**
     * @param string[] $feedUrls
     * @param array $parameters
     *
     * @return string[]
     */
    private function getUrlsFromNewsFeeds($feedUrls, $parameters)
    {
        $urlsFromFeed = [];

        foreach ($feedUrls as $feedUrl) {
            $newsFeed = $this->retrieveSitemap($feedUrl, $parameters);

            if (!empty($newsFeed)) {
                $urlsFromFeed = array_merge($urlsFromFeed, $newsFeed->getUrls());
            }
        }

        return $urlsFromFeed;
    }

    /**
     * @param WebSite $website
     *
     * @return WebsiteRssFeedFinder
     */
    private function getWebsiteRssFeedFinder(Website $website)
    {
        if (empty($this->websiteRssFeedFinder)) {
            $feedFinderConfiguration = new WebsiteRssFeedFinderConfiguration();
            $feedFinderConfiguration->setHttpClient($this->httpClientService->get());
            $feedFinderConfiguration->setRootUrl($website->getCanonicalUrl());

            $this->websiteRssFeedFinder = new WebsiteRssFeedFinder($feedFinderConfiguration);
        }

        return $this->websiteRssFeedFinder;
    }
}
