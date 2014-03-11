<?php
namespace SimplyTestable\ApiBundle\Entity\Stripe;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;
use webignition\Model\Stripe\Event\Event as StripeEventModel;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="StripeEvent",
 *     indexes={
 *         @ORM\Index(name="type_idx", columns={"type"})
 *     }
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
     *
     * @var string
     * 
     * @ORM\Column(type="text", nullable=true, name="data")
     * @SerializerAnnotation\Expose
     */
    protected $stripeEventData;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\User
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedUser")
     * 
     * @SerializerAnnotation\Expose 
     */
    protected $user;
    
    
    /**
     *
     * @var boolean
     * 
     * @ORM\Column(type="boolean", options={"default" = 0})
     * @SerializerAnnotation\Expose
     */    
    private $isProcessed = false;
    
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedUser() {
        if (is_null($this->getUser())) {
            return null;
        }
        
        return $this->getUser()->getUsername();
    }    
    
    
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

    /**
     *
     * @param string $data
     * @return Event
     */
    public function setStripeEventData($stripeEventData)
    {
        $this->stripeEventData = $stripeEventData;
    
        return $this;
    }

    /**
     *
     * @return string 
     */
    public function getStripeEventData()
    {
        return $this->stripeEventData;
    }
    
    
    /**
     * 
     * @return \webignition\Model\Stripe\Event\Event
     */
    public function getStripeEventObject() {        
        return (is_null($this->getStripeEventData())) ? null : new StripeEventModel($this->getStripeEventData());
    }

    /**
     * Set user
     *
     * @param SimplyTestable\ApiBundle\Entity\User $user
     * @return Event
     */
    public function setUser(\SimplyTestable\ApiBundle\Entity\User $user)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return SimplyTestable\ApiBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function hasUser() {
        return !is_null($this->getUser());
    }
    
    
    /**
     * 
     * @param boolean $isProcessed
     */
    public function setIsProcessed($isProcessed) {
        $this->isProcessed = $isProcessed;
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function getIsProcessed() {
        return $this->isProcessed;
    }
}