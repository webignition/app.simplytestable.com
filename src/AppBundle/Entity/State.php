<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\StateRepository")
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
     * @param AppBundle\Entity\State $nextState
     * @return State
     */
    public function setNextState(\AppBundle\Entity\State $nextState = null)
    {
        $this->nextState = $nextState;
        return $this;
    }

    /**
     * Get nextState
     *
     * @return AppBundle\Entity\State 
     */
    public function getNextState()
    {
        return $this->nextState;
    }
    
    
    /**
     *
     * @return string
     */
    public function __toString() {
        return $this->getName();
    }
    
    
    /**
     *
     * @param State $state
     * @return boolean
     */
    public function equals(State $state) {
        return $this->getName() == $state->getName();
    }
}