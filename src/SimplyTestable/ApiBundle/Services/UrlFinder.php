<?php

namespace SimplyTestable\ApiBundle\Services;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Http\Exception\RequestException;
use SimplyTestable\ApiBundle\Entity\WebSite;
use webignition\NormalisedUrl\NormalisedUrl;
use webignition\Url\Url;
use webignition\WebResource\Sitemap\Sitemap;
use webignition\WebsiteRssFeedFinder\WebsiteRssFeedFinder;
use webignition\WebsiteSitemapFinder\WebsiteSitemapFinder;
use webignition\WebsiteSitemapRetriever\Configuration\Configuration as SitemapRetrieverConfiguration;
use webignition\WebsiteSitemapRetriever\WebsiteSitemapRetriever;

class UrlFinder
{
    const DEFAULT_SITEMAP_RETRIEVER_TOTAL_TRANSFER_TIMEOUT = 30;
    const PARAMETER_KEY_COOKIES = 'cookies';

    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @var int|float
     */
    private $sitemapRetrieverTotalTransferTimeout;

    /**
     * @param HttpClientService $httpClientService
     * @param float|null $sitemapRetrieverTotalTransferTimeout
     */
    public function __construct(HttpClientService $httpClientService, $sitemapRetrieverTotalTransferTimeout = null)
    {
        $this->httpClientService = $httpClientService;
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
        $sitemapFinder = $this->createSitemapFinder();
        $sitemapFinder->getConfiguration()->setRootUrl($website->getCanonicalUrl());
        $sitemapFinder->getUrlLimitListener()->setSoftLimit($softLimit);

        $this->httpClientService->prepareRequest(
            $sitemapFinder->getConfiguration()->getBaseRequest(),
            $parameters
        );

        if (isset($parameters[self::PARAMETER_KEY_COOKIES])) {
            $sitemapFinder->getConfiguration()->setCookies($parameters[self::PARAMETER_KEY_COOKIES]);
            $sitemapFinder->getSitemapRetriever()->getConfiguration()->setCookies(
                $parameters[self::PARAMETER_KEY_COOKIES]
            );
        }

        $sitemaps = $sitemapFinder->getSitemaps();
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
     * @param Sitemap $sitemap
     * @param int $softLimit
     * @param int $count
     *
     * @return string[]
     */
    private function getUrlsFromSingleSitemap(Sitemap $sitemap, $softLimit, $count)
    {
        if (!$sitemap->isIndex()) {
            return $sitemap->getUrls();
        }

        $urls = array();
        foreach ($sitemap->getChildren() as $childSitemap) {
            if (!is_null($softLimit)  && ($count + count($urls) >= $softLimit)) {
                return $urls;
            }

            /* @var $childSitemap Sitemap */
            if (is_null($childSitemap->getContent())) {
                $sitemapRetriever = new WebsiteSitemapRetriever();
                $sitemapRetriever->getConfiguration()->setBaseRequest($this->httpClientService->getRequest());
                $sitemapRetriever->getConfiguration()->disableRetrieveChildSitemaps();
                $sitemapRetriever->retrieve($childSitemap);
            }

            $childSitemapUrls = $this->getUrlsFromSingleSitemap($childSitemap, $softLimit, $count + count($urls));
            $urls = array_merge($urls, $childSitemapUrls);
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

        if (is_null($feedUrls)) {
            return array();
        }

        $urlsFromFeed = array();

        foreach ($feedUrls as $feedUrl) {
            $urlsFromFeed = array_merge($urlsFromFeed, $this->getUrlsFromNewsFeed($feedUrl, $parameters));
        }

        return $urlsFromFeed;
    }

    /**
     *
     * @param string $feedUrl
     * @param array $parameters
     *
     * @return array
     */
    private function getUrlsFromNewsFeed($feedUrl, $parameters)
    {
        try {
            $request = $this->httpClientService->getRequest($feedUrl);

            $this->httpClientService->prepareRequest($request, $parameters);

            $response = $request->send();
        } catch (RequestException $requestException) {
            return [];
        } catch (InvalidArgumentException $e) {
            return [];
        }

        $simplepie = new \SimplePie();
        $simplepie->set_raw_data($response->getBody(true));
        @$simplepie->init();

        $items = $simplepie->get_items();

        $urls = [];
        foreach ($items as $item) {
            /* @var $item \SimplePie_Item */
            $url = new NormalisedUrl($item->get_permalink());
            if (!in_array((string)$url, $urls)) {
                $urls[] = (string)$url;
            }
        }

        return $urls;
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

        $urlsFromFeed = [];

        foreach ($feedUrls as $feedUrl) {
            $urlsFromFeed = array_merge(
                $urlsFromFeed,
                $this->getUrlsFromNewsFeed($feedUrl, $parameters)
            );
        }

        return empty($urlsFromFeed)
            ? []
            : $urlsFromFeed;
    }

    /**
     * @return WebsiteSitemapFinder
     */
    private function createSitemapFinder()
    {
        $sitemapFinder = new WebsiteSitemapFinder();
        $sitemapFinder->getConfiguration()->setBaseRequest($this->httpClientService->getRequest());
        $sitemapFinder->getSitemapRetriever()->getConfiguration()->disableRetrieveChildSitemaps();

        $sitemapFinderRetriever = $sitemapFinder->getSitemapRetriever();
        $sitemapRetrieverConfiguration = $sitemapFinderRetriever->getConfiguration();
        $sitemapRetrieverTransferTimeout = $sitemapRetrieverConfiguration->getTotalTransferTimeout();

        if ($sitemapRetrieverTransferTimeout === SitemapRetrieverConfiguration::DEFAULT_TOTAL_TRANSFER_TIMEOUT) {
            $sitemapRetrieverConfiguration->setTotalTransferTimeout($this->sitemapRetrieverTotalTransferTimeout);
        }

        return $sitemapFinder;
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
