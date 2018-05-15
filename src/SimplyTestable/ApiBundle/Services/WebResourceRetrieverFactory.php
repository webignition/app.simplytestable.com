<?php

namespace SimplyTestable\ApiBundle\Services;

use GuzzleHttp\Client as HttpClient;
use webignition\WebResource\Retriever;
use webignition\WebResource\WebPage\WebPage;

class WebResourceRetrieverFactory
{
    const ALLOW_UNKNOWN_RESOURCE_TYPES = true;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return Retriever
     */
    public function create()
    {
        $allowedContentTypes = WebPage::getModelledContentTypeStrings();

        return new Retriever(
            $this->httpClient,
            $allowedContentTypes,
            self::ALLOW_UNKNOWN_RESOURCE_TYPES
        );
    }
}
