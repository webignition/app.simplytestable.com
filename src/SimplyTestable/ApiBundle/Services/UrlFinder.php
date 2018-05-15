<?php

namespace SimplyTestable\ApiBundle\Services;

use GuzzleHttp\Psr7\Request;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\WebSite;
use webignition\Url\Url;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResource\Sitemap\Factory as SitemapFactory;
use webignition\WebResourceInterfaces\SitemapInterface;
use webignition\WebsiteRssFeedFinder\WebsiteRssFeedFinder;
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
     * @var WebResourceRetriever
     */
    private $webResourceRetriever;

    /**
     * @var SitemapFactory
     */
    private $sitemapFactory;

    /**
     * @var int|float
     */
    private $sitemapRetrieverTotalTransferTimeout;

    /**
     * @var WebsiteSitemapFinder
     */
    private $websiteSitemapFinder;

    /**
     * @var WebsiteRssFeedFinder
     */
    private $websiteRssFeedFinder;

    /**
     * @param HttpClientService $httpClientService
     * @param WebResourceRetriever $webResourceService
     * @param SitemapFactory $sitemapFactory
     * @param WebsiteSitemapFinder $websiteSitemapFinder
     * @param WebsiteRssFeedFinder $websiteRssFeedFinder
     * @param float|null $sitemapRetrieverTotalTransferTimeout
     */
    public function __construct(
        HttpClientService $httpClientService,
        WebResourceRetriever $webResourceService,
        SitemapFactory $sitemapFactory,
        WebsiteSitemapFinder $websiteSitemapFinder,
        WebsiteRssFeedFinder $websiteRssFeedFinder,
        $sitemapRetrieverTotalTransferTimeout = null
    ) {
        $this->httpClientService = $httpClientService;
        $this->webResourceRetriever = $webResourceService;
        $this->sitemapFactory = $sitemapFactory;
        $this->websiteSitemapFinder = $websiteSitemapFinder;
        $this->websiteRssFeedFinder = $websiteRssFeedFinder;

        $this->sitemapRetrieverTotalTransferTimeout = (empty($sitemapRetrieverTotalTransferTimeout))
            ? self::DEFAULT_SITEMAP_RETRIEVER_TOTAL_TRANSFER_TIMEOUT
            : $sitemapRetrieverTotalTransferTimeout;
    }

    /**
     * Get collection of URLs to be tested for a given website
     *
     * @param Job $job
     * @param int $softLimit
     * @return string[]
     */
    public function getUrls(Job $job, $softLimit)
    {
        $collectedUrls = $this->collectUrls($job, $softLimit);

        return $this->filterUrlsToWebsiteHost($job->getWebsite(), $collectedUrls);
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
        $filteredUrls = [];

        foreach ($urls as $url) {
            $urlObject = new Url($url);
            if ($urlObject->hasHost() && $urlObject->getHost()->isEquivalentTo($websiteUrl->getHost(), ['www'])) {
                $filteredUrls[] = $url;
            }
        }

        return $filteredUrls;
    }

    /**
     * @param Job $job
     * @param int $softLimit
     *
     * @return string[]
     */
    private function collectUrls(Job $job, $softLimit)
    {
        $urlsFromSitemap = $this->getUrlsFromSitemap($job, $softLimit);
        if (count($urlsFromSitemap)) {
            if (count($urlsFromSitemap) > $softLimit) {
                $urlsFromSitemap = array_slice($urlsFromSitemap, 0, $softLimit);
            }

            return $urlsFromSitemap;
        }

        $this->httpClientService->setRequestHeader('User-Agent', self::FEED_FINDER_USER_AGENT);
        $this->websiteRssFeedFinder->setRootUrl((string)$job->getWebsite());

        $urlsFromRssFeed = $this->getUrlsFromNewsFeeds($this->websiteRssFeedFinder->getRssFeedUrls());
        if (count($urlsFromRssFeed)) {
            return $urlsFromRssFeed;
        }

        $urlsFromAtomFeed = $this->getUrlsFromNewsFeeds($this->websiteRssFeedFinder->getAtomFeedUrls());
        if (count($urlsFromAtomFeed)) {
            return $urlsFromAtomFeed;
        }

        return [];
    }

    /**
     * @param Job $job
     * @param int $softLimit
     *
     * @return string[]
     */
    private function getUrlsFromSitemap(Job $job, $softLimit)
    {
        $jobParametersObject = $job->getParametersObject();

        $cookies = $jobParametersObject->getCookies();
        if (!empty($cookies)) {
            $this->httpClientService->setCookies($cookies);
        }

        $httpAuthenticationCredentials = $jobParametersObject->getHttpAuthenticationCredentials();
        if (!$httpAuthenticationCredentials->isEmpty()) {
            $this->httpClientService->setBasicHttpAuthorization($httpAuthenticationCredentials);
        }

        $this->httpClientService->setRequestHeader('User-Agent', self::SITEMAP_FINDER_USER_AGENT);

        $sitemapUrls = $this->websiteSitemapFinder->findSitemapUrls((string)$job->getWebsite());

        $this->httpClientService->setRequestHeader('User-Agent', self::SITEMAP_RETRIEVER_USER_AGENT);

        /* @var SitemapInterface[] $sitemaps */
        $sitemaps = [];
        foreach ($sitemapUrls as $sitemapUrl) {
            $sitemap = $this->retrieveSitemap($sitemapUrl);

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
                    $this->getUrlsFromSingleSitemap($sitemap, $softLimit, count($urls))
                );
            }
        }

        return $urls;
    }

    /**
     * @param string $url
     *
     * @return SitemapInterface|null
     */
    private function retrieveSitemap($url)
    {
        $sitemap = null;
        $request = new Request('GET', $url);

        try {
            $sitemapResource = $this->webResourceRetriever->retrieve($request);
            $sitemap = $this->sitemapFactory->create(
                $sitemapResource->getResponse(),
                $sitemapResource->getUri()
            );
        } catch (\Exception $exception) {
            // Intentionally swallow all exceptions
        }

        return $sitemap;
    }

    /**
     * @param SitemapInterface $sitemap
     * @param int $softLimit
     * @param int $count
     * @return string[]
     */
    private function getUrlsFromSingleSitemap(SitemapInterface $sitemap, $softLimit, $count)
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

            $childSitemap = $this->retrieveSitemap($childSitemapUrl);

            $transferTime = microtime(true) - $timeBeforeTransfer;
            $totalTransferTime += $transferTime;
            $totalTransferTimeExceeded = $totalTransferTime > $this->sitemapRetrieverTotalTransferTimeout;

            if (empty($childSitemap)) {
                continue;
            }

            $urls = array_merge($urls, $childSitemap->getUrls());
        }

        return $urls;
    }

    /**
     * @param string[] $feedUrls
     *
     * @return string[]
     */
    private function getUrlsFromNewsFeeds($feedUrls)
    {
        $urlsFromFeed = [];

        foreach ($feedUrls as $feedUrl) {
            $newsFeed = $this->retrieveSitemap($feedUrl);

            if (!empty($newsFeed)) {
                $urlsFromFeed = array_merge($urlsFromFeed, $newsFeed->getUrls());
            }
        }

        return $urlsFromFeed;
    }
}
