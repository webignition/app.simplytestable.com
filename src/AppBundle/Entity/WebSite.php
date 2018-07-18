<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="WebSite",indexes={@ORM\Index(name="canonicalUrl_idx", columns={"canonicalUrl"})})
 */
class WebSite
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    protected $canonicalUrl;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $canonicalUrl
     *
     * @return WebSite
     */
    public function setCanonicalUrl($canonicalUrl)
    {
        $this->canonicalUrl = $canonicalUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getCanonicalUrl()
    {
        return $this->canonicalUrl;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getCanonicalUrl();
    }

    /**
     * @param Website $website
     *
     * @return bool
     */
    public function equals(Website $website)
    {
        return $this->getCanonicalUrl() == $website->getCanonicalUrl();
    }
}
