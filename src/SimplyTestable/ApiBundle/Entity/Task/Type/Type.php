<?php
namespace SimplyTestable\ApiBundle\Entity\Task\Type;

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
    
    
    /**
     *
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    protected $description;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass")
     * @ORM\JoinColumn(name="tasktypeclass_id", referencedColumnName="id", nullable=false)
     */
    protected $class;
    
}