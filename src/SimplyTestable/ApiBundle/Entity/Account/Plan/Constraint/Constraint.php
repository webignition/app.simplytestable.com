<?php
namespace SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="AccountPlanConstraint"
 * )
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
     */
    private $name;
    
    
    /**
     * 
     * @var integer
     * 
     * @ORM\Column(type="integer", nullable=true)
     */  
    private $limit = null; 
    
    
    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */    
    private $isAvailable = true;

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
}