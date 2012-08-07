<?php
namespace SimplyTestable\ApiBundle\Services;

use webignition\NormalisedUrl\NormalisedUrl;

/**
 * URL handling tasks 
 */
class DevUrlService extends UrlService { 
    
    const DEV_ENVIRONMENT_PATH_PREFIX = '/app_dev.php';
    
    /**
     *
     * @param string $url
     * @return string
     */
    public function prepare($url) {
        $normalisedUrl = new NormalisedUrl($url);        
        $normalisedUrl->setPath(self::DEV_ENVIRONMENT_PATH_PREFIX . $normalisedUrl->getPath());        
        return (string)$normalisedUrl;
    }
    
}