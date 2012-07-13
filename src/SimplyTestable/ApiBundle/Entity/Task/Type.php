<?php
namespace SimplyTestable\ApiBundle\Entity\Task;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(name="TaskType")
 */
class Type
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
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $name;
}