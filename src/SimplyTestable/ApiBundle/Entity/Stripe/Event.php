<?php
namespace SimplyTestable\ApiBundle\Entity\Stripe;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="StripeEvent"
 * )
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class Event
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
     * @SerializerAnnotation\Expose
     */
    protected $stripeId;
    
    
    /**
     *
     * @var string 
     * 
     * @ORM\Column(type="string")
     * @SerializerAnnotation\Expose
     */    
    protected $type;
    
    
    /**
     *
     * @var boolean
     * 
     * @ORM\Column(type="boolean")
     * @SerializerAnnotation\Expose
     */
    protected $isLive; 

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
     * Set stripeId
     *
     * @param string $stripeId
     * @return Handle
     */
    public function setStripeId($stripeId)
    {
        $this->stripeId = $stripeId;
    
        return $this;
    }

    /**
     * Get stripeId
     *
     * @return string 
     */
    public function getStripeId()
    {
        return $this->stripeId;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Handle
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set isLive
     *
     * @param boolean $isLive
     * @return Handle
     */
    public function setIsLive($isLive)
    {
        $this->isLive = $isLive;
    
        return $this;
    }

    /**
     * Get isLive
     *
     * @return boolean 
     */
    public function getIsLive()
    {
        return $this->isLive;
    }
}