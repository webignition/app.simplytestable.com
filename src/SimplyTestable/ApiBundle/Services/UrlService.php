<?php
namespace SimplyTestable\ApiBundle\Services;


/**
 * URL handling tasks 
 */
class UrlService {    
    
    /**
     * Prepare a URL prior to using it to make a request
     * 
     * The default behaviour is to return the URL unmodified. Environment-specific
     * versions of this service may apply modifications as needed.
     *
     * @param string $url
     * @return string
     */
    public function prepare($url) {
        return $url;
    }
    
}