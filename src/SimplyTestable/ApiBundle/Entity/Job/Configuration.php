<?php
namespace SimplyTestable\ApiBundle\Entity\Job;

use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="JobConfiguration"
 * )
 *
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class Configuration
{    
    /**
     * 
     * @var integer
     * 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * 
     */
    private $id;


    /**
     *
     * @var string
     * @ORM\Column(type="string", unique=false, nullable=false)
     * @SerializerAnnotation\Expose
     */
    private $label;

    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\User
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedUser")
     * @SerializerAnnotation\Expose
     */
    protected $user;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\WebSite
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\WebSite")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=false)
     * 
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedWebsite")
     * @SerializerAnnotation\Expose 
     */
    protected $website;


    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Type
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Type")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id", nullable=false)
     *
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedType")
     * @SerializerAnnotation\Expose
     */
    protected $type;



    /**
     *
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration", mappedBy="jobConfiguration")
     * @SerializerAnnotation\Expose
     */
    protected $taskConfigurations = [];



    /**
     *
     * @var string
     * @ORM\Column(type="text", nullable=true)
     * @SerializerAnnotation\Expose
     */
    protected $parameters;

    
    /**
     *
     * @return string
     */
    public function getPublicSerializedUser() {
        return $this->getUser()->getUsername();
    }
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedWebsite() {
        return (string)$this->getWebsite();
    }

    
    /**
     *
     * @return string
     */
    public function getPublicSerializedType() {
        return (string)$this->getType();
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
     * @param User $user
     * @return Configuration
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get user
     *
     * @return \SimplyTestable\ApiBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }


    /**
     * @return bool
     */
    public function hasUser() {
        return !is_null($this->user);
    }


    /**
     * Set website
     *
     * @param  $website
     * @return Configuration
     */
    public function setWebsite(WebSite $website)
    {
        $this->website = $website;
        return $this;
    }

    /**
     * Get website
     *
     * @return \SimplyTestable\ApiBundle\Entity\Website
     */
    public function getWebsite()
    {
        return $this->website;
    }
    
    /**
     * Set type
     *
     * @param \SimplyTestable\ApiBundle\Entity\Job\Type $type
     * @return Configuration
     */
    public function setType(JobType $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return \SimplyTestable\ApiBundle\Entity\Job\Type
     */
    public function getType()
    {
        return $this->type;
    }    


    
    
    /**
     * Set parameters
     *
     * @param string $parameters
     * @return Configuration
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    
        return $this;
    }

    /**
     * Get parameters
     *
     * @return string
     */
    public function getParameters()
    {
        return $this->parameters;
    }  
    
    /**
     * 
     * @return boolean
     */
    public function hasParameters() {
        return $this->getParameters() != '';
    }
    
    
    /**
     * 
     * @return string
     */
    public function getParametersHash() {
        return md5($this->getParameters());
    }
    
    
    /**
     * 
     * @return \stdClass
     */
    public function getParametersArray() {
        return json_decode($this->getParameters(), true);
    }    
    
    
    /**
     * 
     * @param string $name
     * @return boolean
     */
    public function hasParameter($name) {        
        if (!$this->hasParameters()) {
            return false;
        }
        
        $parameters = json_decode($this->getParameters());
        return isset($parameters->{$name});
    }
    
    
    /**
     * 
     * @param string $name
     * @return mixed
     */
    public function getParameter($name) {
        if (!$this->hasParameter($name)) {
            return null;
        }
        
        $parameters = json_decode($this->getParameters(), true);
        return $parameters[$name];
    }

    /**
     * Set label
     *
     * @param string $label
     * @return Configuration
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string 
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Add taskConfigurations
     *
     * @param TaskConfiguration $taskConfigurations
     * @return Configuration
     */
    public function addTaskConfiguration(TaskConfiguration $taskConfigurations)
    {
        $this->taskConfigurations[] = $taskConfigurations;

        return $this;
    }

    /**
     * Remove taskConfigurations
     *
     * @param TaskConfiguration $taskConfigurations
     */
    public function removeTaskConfiguration(TaskConfiguration $taskConfigurations)
    {
        $this->taskConfigurations->removeElement($taskConfigurations);
    }

    /**
     * Get taskConfigurations
     *
     * @return TaskConfiguration[]
     */
    public function getTaskConfigurations()
    {
        return $this->taskConfigurations;
    }


    /**
     * @return TaskConfigurationCollection
     */
    public function getTaskConfigurationsAsCollection() {
        $collection = new TaskConfigurationCollection();
        foreach ($this->getTaskConfigurations() as $taskConfiguration) {
            $collection->add($taskConfiguration);
        }

        return $collection;
    }


    /**
     * @param Configuration $configuration
     * @return bool
     */
    public function matches(Configuration $configuration) {
        if ($this->getWebsite() != $configuration->getWebsite()) {
            return false;
        }

        if (!$this->getType()->equals($configuration->getType())) {
            return false;
        }

        if (!$this->getTaskConfigurationsAsCollection()->equals($configuration->getTaskConfigurationsAsCollection())) {
            return false;
        }

        return $this->getParameters() == $configuration->getParameters();
    }
}
