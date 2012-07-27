<?php
namespace SimplyTestable\ApiBundle\Entity\Task\Type;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(name="TaskType")
 * @SerializerAnnotation\ExclusionPolicy("all")
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
     * @SerializerAnnotation\Expose
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
    
//    /**
//     *
//     * @var \Doctrine\Common\Collections\Collection
//     * 
//     */
//    protected $jobs;
    
    
    /**
     *
     * @var boolean
     * @ORM\Column(type="boolean", name="selectable", nullable=false)
     */
    protected $selectable = false;    
    

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
     * @param \SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass $class
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
     * @return \SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass
     */
    public function getClass()
    {
        return $this->class;
    }
    
    
    public function __construct()
    {
//        $this->jobs = new \Doctrine\Common\Collections\ArrayCollection();
        $this->selectable = false;
    }
    
    /**
     * Set selectable
     *
     * @param boolean $selectable
     * @return Type
     */
    public function setSelectable($selectable)
    {
        $this->selectable = $selectable;
        return $this;
    }

    /**
     * Get selectable
     *
     * @return boolean 
     */
    public function getSelectable()
    {
        return $this->selectable;
    }

//    /**
//     * Add jobs
//     *
//     * @param SimplyTestable\ApiBundle\Entity\Job\Job $job
//     * @return Type
//     */
//    public function addJob(\SimplyTestable\ApiBundle\Entity\Job\Job $job)
//    {
//        
//        if (!$this->jobs->contains($job)) {
//            $this->jobs[] = $job;
//        }
//        
//        return $this;
//    }
//
//    /**
//     * Remove jobs
//     *
//     * @param SimplyTestable\ApiBundle\Entity\Job\Job $jo
//     */
//    public function removeJob(\SimplyTestable\ApiBundle\Entity\Job\Job $job)
//    {
//        $this->jobs->removeElement($job);
//    }
//
//    /**
//     * Get jobs
//     *
//     * @return Doctrine\Common\Collections\Collection 
//     */
//    public function getJobs()
//    {
//        return $this->jobs;
//    }
    
    
    /**
     *
     * @param Type $taskType
     * @return boolean
     */
    public function equals(Type $taskType) {
        return $this->getName() == $taskType->getName();
    }
}