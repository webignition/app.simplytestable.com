<?php

namespace App\Entity\Job;

use App\Exception\JobMutationException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Task\Task;
use App\Entity\TimePeriod;
use App\Entity\User;
use App\Entity\WebSite;
use App\Entity\State;
use App\Entity\Task\Type\Type as TaskType;
use App\Entity\Job\Type as JobType;
use App\Model\Parameters;
use App\Model\Task\Type\Collection as TaskTypeCollection;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="Job"
 * )
 * @ORM\Entity(repositoryClass="App\Repository\JobRepository")
 */
class Job
{
    const STATE_STARTING = 'job-new';
    const STATE_CANCELLED = 'job-cancelled';
    const STATE_COMPLETED = 'job-completed';
    const STATE_IN_PROGRESS = 'job-in-progress';
    const STATE_PREPARING = 'job-preparing';
    const STATE_QUEUED = 'job-queued';
    const STATE_FAILED_NO_SITEMAP = 'job-failed-no-sitemap';
    const STATE_REJECTED = 'job-rejected';
    const STATE_RESOLVING = 'job-resolving';
    const STATE_RESOLVED = 'job-resolved';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @var WebSite
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\WebSite")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=false)
     */
    private $website;

    /**
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     */
    private $state;

    /**
     * @var int
     */
    private $urlCount;

    /**
     * @var DoctrineCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Task\Task", mappedBy="job")
     */
    private $tasks;

    /**
     * @var JobType
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Job\Type")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id", nullable=true)
     */
    private $type;

    /**
     * @var DoctrineCollection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Task\Type\Type")
     * @ORM\JoinTable(name="JobTaskTypes",
     *      inverseJoinColumns={@ORM\JoinColumn(name="tasktype_id", referencedColumnName="id")},
     *      joinColumns={@ORM\JoinColumn(name="job_id", referencedColumnName="id")}
     * )
     */
    private $requestedTaskTypes;

    /**
     * @var DoctrineCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Job\TaskTypeOptions", mappedBy="job")
     *
     */
    private $taskTypeOptions;

    /**
     * @var TimePeriod
     *
     * @ORM\OneToOne(targetEntity="App\Entity\TimePeriod", cascade={"persist"})
     */
    private $timePeriod;

    /**
     * @var DoctrineCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Job\Ammendment",
     *     mappedBy="job",
     *     cascade={"persist"}
     * )
     */
    private $ammendments;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $parameters;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $isPublic = false;

    /**
     * @var Parameters
     */
    private $parametersObject;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->requestedTaskTypes = new ArrayCollection();
        $this->taskTypeOptions = new ArrayCollection();
        $this->ammendments = new ArrayCollection();
        $this->parameters = '';
    }

    public static function create(User $user, WebSite $webSite, Type $type, State $state, string $parameters)
    {
        $job = new Job();
        $job->user = $user;
        $job->website = $webSite;
        $job->type = $type;
        $job->state = $state;
        $job->parameters = $parameters;

        return $job;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param $website
     */
    public function setWebsite(WebSite $website)
    {
        $this->website = $website;
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
     */
    public function setState(State $state)
    {
        $this->state = $state;
    }

    /**
     * @return State
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param Task $task
     */
    public function addTask(Task $task)
    {
        $this->tasks[] = $task;
    }

    /**
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
     * @param \DateTime $startDateTime
     *
     * @throws JobMutationException
     */
    public function setStartDateTime(\DateTime $startDateTime)
    {
        if (!empty($this->timePeriod)) {
            throw JobMutationException::createStartDateTimeAlreadySetException();
        }

        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime($startDateTime);

        $this->timePeriod = $timePeriod;
    }

    /**
     * @param \DateTime $endDateTime
     *
     * @throws JobMutationException
     */
    public function setEndDateTime(\DateTime $endDateTime)
    {
        if (empty($this->timePeriod)) {
            throw JobMutationException::createStartDateTimeNotSetException();
        }

        $this->timePeriod->setEndDateTime($endDateTime);
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
     */
    public function addRequestedTaskType(TaskType $requestedTaskType)
    {
        if (!$this->requestedTaskTypes->contains($requestedTaskType)) {
            $this->requestedTaskTypes[] = $requestedTaskType;
        }
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
     */
    public function setUrlCount($urlCount)
    {
        $this->urlCount = $urlCount;
    }

    /**
     * @param TaskTypeOptions $taskTypeOptions
     */
    public function addTaskTypeOption(TaskTypeOptions $taskTypeOptions)
    {
        $this->taskTypeOptions[] = $taskTypeOptions;
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
     * @return Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param Ammendment $ammendment
     */
    public function addAmmendment(Ammendment $ammendment)
    {
        $this->ammendments[] = $ammendment;
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
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;
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
     */
    public function setParametersString($parameters)
    {
        $this->parameters = $parameters;
        $this->parametersObject = null;
    }

    /**
     * @return string
     */
    public function getParametersString()
    {
        return $this->parameters;
    }

    /**
     * @return bool
     */
    public function hasParameters()
    {
        return !empty($this->parameters);
    }

    /**
     * @return Parameters
     */
    public function getParameters()
    {
        if (empty($this->parametersObject)) {
            $parametersArray = json_decode($this->parameters, true);

            if (!is_array($parametersArray)) {
                $parametersArray = [];
            }

            $this->parametersObject = new Parameters($parametersArray);
        }

        return $this->parametersObject;
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

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $serializedRequestedTaskTypes = [];

        foreach ($this->requestedTaskTypes as $taskType) {
            /* @var TaskType $taskType */
            $serializedRequestedTaskTypes[] = $taskType->jsonSerialize();
        }

        $serializedTaskTypeOptions = [];

        foreach ($this->taskTypeOptions as $taskTypeOptions) {
            /* @var TaskTypeOptions $taskTypeOptions */
            $taskTypeName = $taskTypeOptions->getTaskType()->getName();

            $serializedTaskTypeOptions[$taskTypeName] = $taskTypeOptions->getOptions();
        }

        $serializedJobData = [
            'id' => $this->id,
            'user' => $this->user->getUsername(),
            'website' => $this->website->getCanonicalUrl(),
            'state' => str_replace('job-', '', $this->state->getName()),
            'url_count' => $this->urlCount,
            'task_types' => $serializedRequestedTaskTypes,
            'task_type_options' => $serializedTaskTypeOptions,
            'type' => $this->type->getName(),
            'parameters' => $this->parameters,
        ];

        if (!empty($this->timePeriod) && !$this->timePeriod->isEmpty()) {
            $serializedJobData['time_period'] = $this->timePeriod->jsonSerialize();
        }

        return $serializedJobData;
    }
}
