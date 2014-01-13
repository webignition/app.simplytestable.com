<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use webignition\Url\Url;

/**
 * @ORM\Entity
 */
class WebSite
{
    /**
     * 
     * @var integer
     * 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     *
     * @var string 
     * 
     * @ORM\Column(type="string", unique=true)
     */
    protected $canonicalUrl;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set canonicalUrl
     *
     * @param string $canonicalUrl
     * @return WebSite
     */
    public function setCanonicalUrl($canonicalUrl)
    {
        $this->canonicalUrl = $canonicalUrl;
        return $this;
    }

    /**
     * Get canonicalUrl
     *
     * @return string 
     */
    public function getCanonicalUrl()
    {
        return $this->canonicalUrl;
    }
    
    
    /**
     *
     * @return string
     */
    public function __toString() {
        return $this->getCanonicalUrl();
    }
    
    
    /**
     *
     * @param Website $website
     * @return boolean
     */
    public function equals(Website $website) {
        return $this->getCanonicalUrl() == $website->getCanonicalUrl();
    }
    
    
    
    /**
     * 
     * @return boolean
     */
    public function isPubliclyRoutable() {
        $url = new Url($this->getCanonicalUrl());
        
        if (!$url->getHost()->isPubliclyRoutable()) {
            return false;
        }
        
        if ($this->isUrlHostDotless($url)) {
            return false;
        }
        
        if ($this->doesUrlHostStartWithDot($url)) {
            return false;
        }
        
        if ($this->doesUrlHostEndWithDot($url)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 
     * @param \webignition\Url\Url $url
     * @return boolean
     */
    private function isUrlHostDotless(Url $url) {        
        return !substr_count($url->getHost()->get(), '.');
    }
    
    
    /**
     * 
     * @param \webignition\Url\Url $url
     * @return boolean
     */
    private function doesUrlHostStartWithDot(Url $url) {
        return strpos($url->getHost()->get(), '.') === 0;
    }
    
    
    /**
     * 
     * @param \webignition\Url\Url $url
     * @return boolean
     */
    private function doesUrlHostEndWithDot(Url $url) {
        return strpos($url->getHost()->get(), '.') === strlen($url->getHost()->get()) - 1;
    }
}