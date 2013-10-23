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
        
        if (!substr_count($url->getHost()->get(), '.') || (strpos($url->getHost()->get(), '.') === 0) || strpos($url->getHost()->get(), '.') === strlen($url->getHost()->get() - 1)) { 
            return false;
        }
        
        return true;
    }
}