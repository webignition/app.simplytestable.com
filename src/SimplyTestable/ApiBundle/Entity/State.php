<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * @ORM\Entity
 */
class State
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
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $name;
    
    
    /**
     *
     * @var State
     * 
     * @ORM\OneToOne(targetEntity="State")
     */
    protected $nextState;

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
     * @return State
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
     * Set nextState
     *
     * @param SimplyTestable\ApiBundle\Entity\State $nextState
     * @return State
     */
    public function setNextState(\SimplyTestable\ApiBundle\Entity\State $nextState = null)
    {
        $this->nextState = $nextState;
        return $this;
    }

    /**
     * Get nextState
     *
     * @return SimplyTestable\ApiBundle\Entity\State 
     */
    public function getNextState()
    {
        return $this->nextState;
    }
}