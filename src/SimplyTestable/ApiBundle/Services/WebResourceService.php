<?php

namespace SimplyTestable\ApiBundle\Services;

use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;
use SimplyTestable\ApiBundle\Exception\WebResourceException;

class WebResourceService {    
    
    const MAX_REDIRECTS = 5;
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\HttpClientService
     */
    private $httpClientService;
    
    
    /**
     * Maps content types to WebResource subclasses
     * 
     * @var array
     */
    private $contentTypeWebResourceMap = array();

    
    /**
     *
     * @param \SimplyTestable\ApiBundle\Services\HttpClientService $httpClientService
     * @param array $contentTypeWebResourceMap
     */
    public function __construct(
            \SimplyTestable\ApiBundle\Services\HttpClientService $httpClientService,
            $contentTypeWebResourceMap)
    {
        $this->httpClientService = $httpClientService;        
        $this->contentTypeWebResourceMap = $contentTypeWebResourceMap;        
    }
    
    
    /**
     * 
     * @return int
     */
    public function getMaxRedirects() {
        return self::MAX_REDIRECTS;
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
     * @param \Guzzle\Http\Message\Request $request
     * @return \webignition\WebResource\WebResource 
     */
    public function get(\Guzzle\Http\Message\Request $request) {        
        // Guzzle seems to be flailing in errors if redirects total more than 4
        $request->getParams()->set('redirect.max', self::MAX_REDIRECTS);
        
        try {
            $response = $request->send();
        } catch (\Guzzle\Http\Exception\ServerErrorResponseException $serverErrorResponseException) {
            $response = $serverErrorResponseException->getResponse();
        } catch (\Guzzle\Http\Exception\ClientErrorResponseException $clientErrorResponseException) {
            $response = $clientErrorResponseException->getResponse();
        }
        
        if ($response->isInformational()) {
            // Interesting to see what makes this happen
            return;
        }
        
        if ($response->isRedirect()) {
            // Shouldn't happen, HTTP client should have the redirect handler
            // enabled, redirects should be followed            
            return;
        }
        
        if ($response->isClientError() || $response->isServerError()) {
            throw new WebResourceException($response, $request); 
        }
        
        $mediaTypeParser = new InternetMediaTypeParser();
        $mediaTypeParser->setAttemptToRecoverFromInvalidInternalCharacter(true);
        $mediaTypeParser->setIgnoreInvalidAttributes(true);
        $contentType = $mediaTypeParser->parse($response->getContentType());               

        $webResourceClassName = $this->getWebResourceClassName($contentType->getTypeSubtypeString());

        $resource = new $webResourceClassName;                
        $resource->setContent($response->getBody(true));                              
        $resource->setContentType((string)$contentType);        
        $resource->setUrl($response->getRequest()->getUrl());          

        return $resource;
    }
    

    /**
     * Get the WebResource subclass name for a given content type
     * 
     * @param string $contentType
     * @return string
     */
    private function getWebResourceClassName($contentType) {
        return (isset($this->contentTypeWebResourceMap[$contentType])) ? $this->contentTypeWebResourceMap[$contentType] : $this->contentTypeWebResourceMap['default'];
    }
    
}