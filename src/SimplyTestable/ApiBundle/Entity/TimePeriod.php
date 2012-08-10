<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class TimePeriod
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
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @SerializerAnnotation\Expose
     */
    protected $startDateTime;
    
    
    /**
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @SerializerAnnotation\Expose
     */
    protected $endDateTime;    

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
     * Set startDateTime
     *
     * @param \DateTime $startDateTime
     * @return TimePeriod
     */
    public function setStartDateTime($startDateTime)
    {
        $this->startDateTime = $startDateTime;
    
        return $this;
    }

    /**
     * Get startDateTime
     *
     * @return \DateTime 
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * Set endDateTime
     *
     * @param \DateTime $endDateTime
     * @return TimePeriod
     */
    public function setEndDateTime($endDateTime)
    {
        $this->endDateTime = $endDateTime;
    
        return $this;
    }

    /**
     * Get endDateTime
     *
     * @return \DateTime 
     */
    public function getEndDateTime()
    {
        return $this->endDateTime;
    }
}