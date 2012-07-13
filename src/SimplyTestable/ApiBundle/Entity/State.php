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
}