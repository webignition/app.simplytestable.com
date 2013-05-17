<?php
namespace SimplyTestable\ApiBundle\Entity\Account\Plan;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="AccountPlan"
 * )
 */
class Plan
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
     * @ORM\Column(type="string", unique=true)
     */
    private $name;
    
    
    /**
     *
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\OneToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint", mappedBy="plan", cascade={"persist", "remove"})   
     */ 
    private $constraints;  
    
    
    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $isVisible = false;
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->constraints = new \Doctrine\Common\Collections\ArrayCollection();
        $this->isVisible = false;
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
     * Set name
     *
     * @param string $name
     * @return Plan
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
     * Set isVisible
     *
     * @param boolean $isVisible
     * @return Plan
     */
    public function setIsVisible($isVisible)
    {
        $this->isVisible = $isVisible;
    
        return $this;
    }

    /**
     * Get isVisible
     *
     * @return boolean 
     */
    public function getIsVisible()
    {
        return $this->isVisible;
    }

    /**
     * Add constraint
     *
     * @param SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint $constraints
     * @return Plan
     */
    public function addConstraint(\SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint $constraint)
    {
        $this->constraints[] = $constraint;
        $constraint->setPlan($this);
    
        return $this;
    }

    /**
     * Remove constraint
     *
     * @param SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint $constraints
     */
    public function removeConstraint(\SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint $constraint)
    {
        $this->constraints->removeElement($constraint);
    }

    /**
     * Get constraints
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getConstraints()
    {
        return $this->constraints;
    }
    
    
    /**
     * 
     * @param string $constraintName
     * @return boolean
     */
    public function hasConstraintNamed($constraintName) {
        return !is_null($this->getConstraintNamed($constraintName));
    }
    
    
    /**
     * 
     * @param string $constraintName
     * @return Constraint
     */
    public function getConstraintNamed($constraintName) {
        foreach ($this->getConstraints() as $constraint) {
            if ($constraint->getName() === $constraintName)  {
                return $constraint;
            }
        }
        
        return null;        
    }
}