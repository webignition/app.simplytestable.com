<?php

namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\WebSite;
use webignition\Url\Url;
use webignition\WebResource\Service\Service as WebResourceService;
use webignition\WebResource\Sitemap\Factory as SitemapFactory;
use webignition\WebResource\Sitemap\Sitemap;
use webignition\WebsiteRssFeedFinder\WebsiteRssFeedFinder;
use webignition\WebsiteSitemapFinder\Configuration as WebsiteSitemapFinderConfiguration;
use webignition\WebsiteSitemapFinder\WebsiteSitemapFinder;

class UrlFinder
{
    const DEFAULT_SITEMAP_RETRIEVER_TOTAL_TRANSFER_TIMEOUT = 30;
    const PARAMETER_KEY_COOKIES = 'cookies';

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
        $baseRequest = $this->httpClientService->getRequest();
        $baseRequest->setUrl($website->getCanonicalUrl());
        $this->httpClientService->prepareRequest(
            $baseRequest,
            $parameters
        );

        $configuration = new WebsiteSitemapFinderConfiguration([
            WebsiteSitemapFinderConfiguration::KEY_ROOT_URL => $website->getCanonicalUrl(),
            WebsiteSitemapFinderConfiguration::KEY_BASE_REQUEST => $baseRequest,
        ]);

        $sitemapFinder = new WebsiteSitemapFinder($configuration);

        $sitemapUrls = $sitemapFinder->findSitemapUrls();
        $sitemaps = [];

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
        try {
            $request = $this->httpClientService->getRequest($url);
            $this->httpClientService->prepareRequest(
                $request,
                $parameters
            );

            $sitemapResource = $this->webResourceService->get($request);
            $sitemap = $this->sitemapFactory->create($sitemapResource->getHttpResponse());

            return $sitemap;

        } catch (\Exception $exception) {
            return null;
        }
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
        $feedFinder = $this->createWebsiteRssFeedFinder($website, $parameters);
        $feedUrls = $feedFinder->getRssFeedUrls();

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
        $feedFinder = $this->createWebsiteRssFeedFinder($website, $parameters);
        $feedUrls = $feedFinder->getAtomFeedUrls();

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
     * @param array $parameters
     *
     * @return WebsiteRssFeedFinder
     */
    private function createWebsiteRssFeedFinder(Website $website, $parameters)
    {
        $feedFinder = new WebsiteRssFeedFinder();
        $feedFinder->getConfiguration()->setBaseRequest($this->httpClientService->getRequest());
        $feedFinder->getConfiguration()->setRootUrl($website->getCanonicalUrl());
        $feedFinder->getConfiguration()->getBaseRequest()->getClient()->setUserAgent(
            'ST News Feed URL Retriever/0.1 (http://bit.ly/RlhKCL)'
        );

        $this->httpClientService->prepareRequest(
            $feedFinder->getConfiguration()->getBaseRequest(),
            $parameters
        );

        if (isset($parameters[self::PARAMETER_KEY_COOKIES])) {
            $feedFinder->getConfiguration()->setCookies($parameters[self::PARAMETER_KEY_COOKIES]);
        }

        return $feedFinder;
    }
}
