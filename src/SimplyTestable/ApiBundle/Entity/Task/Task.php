<?php
namespace SimplyTestable\ApiBundle\Entity\Task;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

/**
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="Task",
 *     indexes={
 *         @ORM\Index(name="remoteId_idx", columns={"remoteId"})
 *     }
 * )
 * @SerializerAnnotation\ExclusionPolicy("all")
 * @ORM\Entity(repositoryClass="SimplyTestable\ApiBundle\Repository\TaskRepository")
 *
 */
class Task
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SerializerAnnotation\Expose
     */
    protected $id;

    /**
     * @var Job
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Job", inversedBy="tasks")
     * @ORM\JoinColumn(name="job_id", referencedColumnName="id", nullable=false)
     */
    protected $job;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     * @SerializerAnnotation\Expose
     */
    protected $url;

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
     * @var Worker
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Worker", inversedBy="tasks")
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedWorker")
     * @SerializerAnnotation\Expose
     */
    protected $worker;

    /**
     * @var TaskType
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Type\Type")
     * @ORM\JoinColumn(name="tasktype_id", referencedColumnName="id", nullable=false)
     *
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedType")
     * @SerializerAnnotation\Expose
     */
    protected $type;

    /**
     * @var TimePeriod
     *
     * @ORM\OneToOne(targetEntity="SimplyTestable\ApiBundle\Entity\TimePeriod", cascade={"persist", "remove"})
     * @SerializerAnnotation\Expose
     */
    protected $timePeriod;

    /**
     * @var int
     *
     * @ORM\Column(type="bigint", nullable=true)
     * @SerializerAnnotation\Expose
     * @SerializerAnnotation\SerializedName("remote_id")
     * @SerializerAnnotation\Expose
     */
    protected $remoteId;

    /**
     * @var Output
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Output", cascade={"persist"})
     * @SerializerAnnotation\Expose
     */
    protected $output;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $parameters;

    /**
     * @return string
     */
    public function getPublicSerializedState()
    {
        return str_replace('task-', '', (string)$this->getState());
    }

    /**
     * @return string
     */
    public function getPublicSerializedType()
    {
        return (string)$this->getType();
    }

    /**
     * @return string
     */
    public function getPublicSerializedWorker()
    {
        return (is_null($this->getWorker())) ? '' : $this->getWorker()->getHostname();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param Job $job
     */
    public function setJob(Job $job)
    {
        $this->job = $job;
    }

    /**
     * @return Job
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param State $state
     */
    public function setState(State $state)
    {
        $this->state = $state;
    }

    public function setNextState()
    {
        if (!is_null($this->getState()->getNextState())) {
            $this->state = $this->getState()->getNextState();
        }
    }

    /**
     * @return State
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param Worker $worker
     */
    public function setWorker(Worker $worker = null)
    {
        $this->worker = $worker;
    }

    public function clearWorker()
    {
        $this->worker = null;
    }

    public function clearRemoteId()
    {
        $this->remoteId = null;
    }

    /**
     * @return Worker
     */
    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * @param Type\Type $type
     */
    public function setType(Type\Type $type)
    {
        $this->type = $type;
    }

    /**
     * @return Type\Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $remoteId
     */
    public function setRemoteId($remoteId)
    {
        $this->remoteId = $remoteId;
    }

    /**
     * @return int
     */
    public function getRemoteId()
    {
        return $this->remoteId;
    }

    /**
     * @param TimePeriod $timePeriod
     */
    public function setTimePeriod(TimePeriod $timePeriod = null)
    {
        $this->timePeriod = $timePeriod;
    }

    /**
     * @return TimePeriod
     */
    public function getTimePeriod()
    {
        return $this->timePeriod;
    }

    /**
     * @param Output $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return Output
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param string $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getParameters()
    {
        return $this->parameters;
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
     * @return mixed
     */
    public function getParameter($name)
    {
        $parametersArray = $this->getParametersArray();

        if (!is_array($parametersArray)) {
            return false;
        }

        if (!isset($parametersArray[$name])) {
            return false;
        }

        return $parametersArray[$name];
    }
}
