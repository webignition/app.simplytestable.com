<?php
namespace SimplyTestable\ApiBundle\Entity\Job;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="Job",
 *     indexes={
 *        @ORM\Index(name="user_index", columns={"user_id"}),
 *        @ORM\Index(name="website_index", columns={"website_id"}),
 *        @ORM\Index(name="state_index", columns={"state_id"})}
 * )
 */
class Job
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
     * @var User
     * 
     * @ORM\Column(type="integer", nullable=false, name="user_id")
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     */
    protected $user;
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\WebSite
     * 
     * @ORM\Column(type="integer", nullable=false, name="website_id")
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\WebSite")
     */
    protected $website;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\State
     * 
     * @ORM\Column(type="integer", nullable=false, name="state_id")
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\State")
     */
    protected $state;     
    
    /**
     *
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\OneToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Task", mappedBy="job")
     */
    protected $tasks;
    public function __construct()
    {
        $this->tasks = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set user
     *
     * @param integer $user
     * @return Job
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get user
     *
     * @return integer 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set website
     *
     * @param integer $website
     * @return Job
     */
    public function setWebsite($website)
    {
        $this->website = $website;
        return $this;
    }

    /**
     * Get website
     *
     * @return integer 
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set state
     *
     * @param integer $state
     * @return Job
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Get state
     *
     * @return integer 
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Add tasks
     *
     * @param SimplyTestable\ApiBundle\Entity\Task\Task $tasks
     * @return Job
     */
    public function addTask(\SimplyTestable\ApiBundle\Entity\Task\Task $tasks)
    {
        $this->tasks[] = $tasks;
        return $this;
    }

    /**
     * Remove tasks
     *
     * @param <variableType$tasks
     */
    public function removeTask(\SimplyTestable\ApiBundle\Entity\Task\Task $tasks)
    {
        $this->tasks->removeElement($tasks);
    }

    /**
     * Get tasks
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getTasks()
    {
        return $this->tasks;
    }
}