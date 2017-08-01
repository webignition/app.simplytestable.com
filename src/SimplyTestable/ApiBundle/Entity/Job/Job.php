<?php
namespace SimplyTestable\ApiBundle\Entity\Job;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;
use SimplyTestable\ApiBundle\Model\Task\Type\Collection as TaskTypeCollection;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="Job"
 * )
 * @ORM\Entity(repositoryClass="SimplyTestable\ApiBundle\Repository\JobRepository")
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class Job
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @SerializerAnnotation\Expose
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedUser")
     * @SerializerAnnotation\Expose
     */
    protected $user;

    /**
     * @var WebSite
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\WebSite")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=false)
     *
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedWebsite")
     * @SerializerAnnotation\Expose
     */
    protected $website;

    /**
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     *
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedState")
     * @SerializerAnnotation\Expose
     */
    protected $state;

    /**
     * @var int
     * @SerializerAnnotation\Expose
     */
    protected $urlCount;

    /**
     * @var DoctrineCollection
     *
     * @ORM\OneToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Task", mappedBy="job")
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedTasks")
     */
    private $tasks;

    /**
     * @var JobType
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Type")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id", nullable=true)
     *
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedType")
     * @SerializerAnnotation\Expose
     */
    protected $type;

    /**
     * @var DoctrineCollection
     *
     * @ORM\ManyToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Type\Type")
     * @ORM\JoinTable(name="JobTaskTypes",
     *      inverseJoinColumns={@ORM\JoinColumn(name="tasktype_id", referencedColumnName="id")},
     *      joinColumns={@ORM\JoinColumn(name="job_id", referencedColumnName="id")}
     * )
     *
     * @SerializerAnnotation\SerializedName("task_types")
     * @SerializerAnnotation\Expose
     */
    private $requestedTaskTypes;

    /**
     * @var DoctrineCollection
     *
     * @ORM\OneToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions", mappedBy="job")
     *
     */
    private $taskTypeOptions;

    /**
     * @var TimePeriod
     *
     * @ORM\OneToOne(targetEntity="SimplyTestable\ApiBundle\Entity\TimePeriod", cascade={"persist"})
     * @SerializerAnnotation\Expose
     */
    protected $timePeriod;

    /**
     * @var DoctrineCollection
     *
     * @ORM\OneToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Ammendment", mappedBy="job", cascade={"persist"})
     */
    private $ammendments;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @SerializerAnnotation\Expose
     */
    protected $parameters;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     * @SerializerAnnotation\Expose
     */
    private $isPublic = false;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->requestedTaskTypes = new ArrayCollection();
        $this->taskTypeOptions = new ArrayCollection();
        $this->ammendments = new ArrayCollection();
        $this->parameters = '';
    }

    /**
     * @return string
     */
    public function getPublicSerializedUser()
    {
        return $this->getUser()->getUsername();
    }

    /**
     * @return string
     */
    public function getPublicSerializedWebsite()
    {
        return (string)$this->getWebsite();
    }

    /**
     * @return string
     */
    public function getPublicSerializedState()
    {
        return str_replace('job-', '', (string)$this->getState());
    }

    /**
     * @return string
     */
    public function getPublicSerializedType()
    {
        return (string)$this->getType();
    }

    /**
     * @return DoctrineCollection
     */
    public function getPublicSerializedTasks()
    {
        $tasks = clone $this->getTasks();
        foreach ($tasks as $task) {
            /* @var $task Task */
            $task->setOutput(null);
        }

        return $tasks;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param User $user
     *
     * @return Job
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param  $website
     *
     * @return Job
     */
    public function setWebsite(WebSite $website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return WebSite
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param State $state
     *
     * @return Job
     */
    public function setState(State $state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return State
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return Job
     */
    public function setNextState()
    {
        if (!is_null($this->getState()->getNextState())) {
            $this->state = $this->getState()->getNextState();
        }

        return $this;
    }

    /**
     * @param Task $task
     *
     * @return Job
     */
    public function addTask(Task $task)
    {
        $this->tasks[] = $task;

        return $this;
    }

    /**
     * Remove tasks
     *
     * @param Task $task
     */
    public function removeTask(Task $task)
    {
        $this->tasks->removeElement($task);
    }

    /**
     * @return DoctrineCollection
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * @param TimePeriod $timePeriod
     *
     * @return Job
     */
    public function setTimePeriod(TimePeriod $timePeriod = null)
    {
        $this->timePeriod = $timePeriod;

        return $this;
    }

    /**
     * @return TimePeriod
     */
    public function getTimePeriod()
    {
        return $this->timePeriod;
    }

    /**
     * @param TaskType $requestedTaskType
     *
     * @return Job
     */
    public function addRequestedTaskType(TaskType $requestedTaskType)
    {
        if (!$this->requestedTaskTypes->contains($requestedTaskType)) {
            $this->requestedTaskTypes[] = $requestedTaskType;
        }

        return $this;
    }

    /**
     * @param TaskType $requestedTaskType
     */
    public function removeRequestedTaskType(TaskType $requestedTaskType)
    {
        $this->requestedTaskTypes->removeElement($requestedTaskType);
    }

    /**
     * @return DoctrineCollection
     */
    public function getRequestedTaskTypes()
    {
        return $this->requestedTaskTypes;
    }

    /**
     * @return TaskTypeCollection
     */
    public function getTaskTypeCollection()
    {
        $collection = new TaskTypeCollection();

        foreach ($this->getRequestedTaskTypes() as $taskType) {
            $collection->add($taskType);
        }

        return $collection;
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function equals(Job $job)
    {
        if (!$this->getState()->equals($job->getState())) {
            return false;
        }

        if (!$this->getUser()->equals($job->getUser())) {
            return false;
        }

        if (!$this->getWebsite()->equals($job->getWebsite())) {
            return false;
        }

        if (!$this->requestedTaskTypesEquals($job->getRequestedTaskTypes())) {
            return false;
        }

        return true;
    }

    /**
     * @param DoctrineCollection $requestedTaskTypes
     *
     * @return bool
     */
    private function requestedTaskTypesEquals(DoctrineCollection $requestedTaskTypes)
    {
        /* @var $comparatorTaskType TaskType */
        /* @var $requestedTaskType TaskType */
        foreach ($requestedTaskTypes as $comparatorTaskType) {
            foreach ($this->getRequestedTaskTypes() as $requestedTaskType) {
                if (!$requestedTaskType->equals(($comparatorTaskType))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return int
     */
    public function getUrlCount()
    {
        return $this->urlCount;
    }

    /**
     * @param int $urlCount
     *
     * @return Job
     */
    public function setUrlCount($urlCount)
    {
        $this->urlCount = $urlCount;
        return $this;
    }

    /**
     * @param TaskTypeOptions $taskTypeOptions
     * @return Job
     */
    public function addTaskTypeOption(TaskTypeOptions $taskTypeOptions)
    {
        $this->taskTypeOptions[] = $taskTypeOptions;

        return $this;
    }

    /**
     * @param TaskTypeOptions $taskTypeOptions
     */
    public function removeTaskTypeOption(TaskTypeOptions $taskTypeOptions)
    {
        $this->taskTypeOptions->removeElement($taskTypeOptions);
    }

    /**
     * @return DoctrineCollection
     */
    public function getTaskTypeOptions()
    {
        return $this->taskTypeOptions;
    }

    /**
     * @param Type $type
     *
     * @return Job
     */
    public function setType(JobType $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param Ammendment $ammendment
     * @return Job
     */
    public function addAmmendment(Ammendment $ammendment)
    {
        $this->ammendments[] = $ammendment;
        return $this;
    }

    /**
     * @param Ammendment $ammendment
     */
    public function removeAmmendment(Ammendment $ammendment)
    {
        $this->ammendments->removeElement($ammendment);
    }

    /**
     * @return DoctrineCollection
     */
    public function getAmmendments()
    {
        return $this->ammendments;
    }

    /**
     * @param boolean $isPublic
     *
     * @return Job
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsPublic()
    {
        return filter_var($this->isPublic, FILTER_VALIDATE_BOOLEAN);
    }


    /**
     * @param string $parameters
     *
     * @return Job
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @return string
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return bool
     */
    public function hasParameters()
    {
        return $this->getParameters() != '';
    }

    /**
     * @return string
     */
    public function getParametersHash()
    {
        return md5($this->getParameters());
    }

    /**
     * @return array
     */
    public function getParametersArray()
    {
        return json_decode($this->getParameters(), true);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasParameter($name)
    {
        if (!$this->hasParameters()) {
            return false;
        }

        $parameters = json_decode($this->getParameters());
        return isset($parameters->{$name});
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        if (!$this->hasParameter($name)) {
            return null;
        }

        $parameters = json_decode($this->getParameters(), true);

        return $parameters[$name];
    }

    /**
     * @return int[]
     */
    public function getTaskIds()
    {
        $taskIds = [];

        foreach ($this->getTasks() as $task) {
            $taskIds[] = $task->getId();
        }

        return $taskIds;
    }
}
