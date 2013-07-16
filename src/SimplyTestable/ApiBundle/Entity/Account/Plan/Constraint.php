<?php
namespace SimplyTestable\ApiBundle\Entity\Account\Plan;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="AccountPlanConstraint"
 * )
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class Constraint
{
    
    /**
     * 
     * @var integer
     * 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;  
    

    /**
     *
     * @var string
     * @ORM\Column(type="string")
     * @SerializerAnnotation\Expose
     */
    private $name;
    
    
    /**
     * 
     * @var integer
     * 
     * @ORM\Column(type="integer", nullable=true, name="limit_threshold")
     * @SerializerAnnotation\Expose
     */  
    private $limit = null; 
    
    
    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     * @SerializerAnnotation\Expose
     */    
    private $isAvailable = true;
    

    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\Account\Plan\Plan
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Account\Plan\Plan", inversedBy="constraints")
     * @ORM\JoinColumn(name="plan_id", referencedColumnName="id", nullable=false)     
     */  
    private $plan; 
    

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
     * Set name
     *
     * @param string $name
     * @return Constraint
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set limit
     *
     * @param integer $limit
     * @return Constraint
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    
        return $this;
    }

    /**
     * Get limit
     *
     * @return integer 
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Set isAvailable
     *
     * @param boolean $isAvailable
     * @return Constraint
     */
    public function setIsAvailable($isAvailable)
    {
        $this->isAvailable = $isAvailable;
    
        return $this;
    }

    /**
     * Get isAvailable
     *
     * @return boolean 
     */
    public function getIsAvailable()
    {
        return $this->isAvailable;
    }

    /**
     * Set plan
     *
     * @param SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $plan
     * @return Constraint
     */
    public function setPlan(\SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $plan)
    {
        $this->plan = $plan;
    
        return $this;
    }

    /**
     * Get plan
     *
     * @return SimplyTestable\ApiBundle\Entity\Account\Plan\Plan 
     */
    public function getPlan()
    {
        return $this->plan;
    }
}