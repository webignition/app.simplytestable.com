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
     *
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\ManyToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Job", inversedBy="requestedTaskTypes")
     * @ORM\JoinTable(name="JobTaskTypes",
     *      joinColumns={@ORM\JoinColumn(name="tasktype_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="job_id", referencedColumnName="id")}
     * )
     */
    protected $jobs;
    
    
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
        $this->jobs = new \Doctrine\Common\Collections\ArrayCollection();
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

    /**
     * Add jobs
     *
     * @param SimplyTestable\ApiBundle\Entity\Job\Job $jobs
     * @return Type
     */
    public function addJob(\SimplyTestable\ApiBundle\Entity\Job\Job $jobs)
    {
        $this->jobs[] = $jobs;
        return $this;
    }

    /**
     * Remove jobs
     *
     * @param <variableType$jobs
     */
    public function removeJob(\SimplyTestable\ApiBundle\Entity\Job\Job $jobs)
    {
        $this->jobs->removeElement($jobs);
    }

    /**
     * Get jobs
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getJobs()
    {
        return $this->jobs;
    }
}