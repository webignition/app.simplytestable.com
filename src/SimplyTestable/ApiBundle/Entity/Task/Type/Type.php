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
     * @return Type
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
     * Set description
     *
     * @param text $description
     * @return Type
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return text 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set class
     *
     * @param SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass $class
     * @return Type
     */
    public function setClass(\SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass $class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * Get class
     *
     * @return SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass 
     */
    public function getClass()
    {
        return $this->class;
    }
}