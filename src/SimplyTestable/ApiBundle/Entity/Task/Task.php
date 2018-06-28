<?php
namespace SimplyTestable\ApiBundle\Entity\Task;

use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Model\Parameters;

/**
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="Task",
 *     indexes={
 *         @ORM\Index(name="remoteId_idx", columns={"remoteId"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="SimplyTestable\ApiBundle\Repository\TaskRepository")
 *
 */
class Task implements \JsonSerializable
{
    const STATE_CANCELLED = 'task-cancelled';
    const STATE_QUEUED = 'task-queued';
    const STATE_IN_PROGRESS = 'task-in-progress';
    const STATE_COMPLETED = 'task-completed';
    const STATE_AWAITING_CANCELLATION = 'task-awaiting-cancellation';
    const STATE_QUEUED_FOR_ASSIGNMENT = 'task-queued-for-assignment';
    const STATE_FAILED_NO_RETRY_AVAILABLE = 'task-failed-no-retry-available';
    const STATE_FAILED_RETRY_AVAILABLE = 'task-failed-retry-available';
    const STATE_FAILED_RETRY_LIMIT_REACHED = 'task-failed-retry-limit-reached';
    const STATE_SKIPPED = 'task-skipped';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
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
     */
    protected $url;

    /**
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     */
    protected $state;

    /**
     * @var Worker
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Worker", inversedBy="tasks")
     */
    protected $worker;

    /**
     * @var TaskType
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Type\Type")
     * @ORM\JoinColumn(name="tasktype_id", referencedColumnName="id", nullable=false)
     */
    protected $type;

    /**
     * @var TimePeriod
     *
     * @ORM\OneToOne(targetEntity="SimplyTestable\ApiBundle\Entity\TimePeriod", cascade={"persist", "remove"})
     */
    protected $timePeriod;

    /**
     * @var int
     *
     * @ORM\Column(type="bigint", nullable=true)
     */
    protected $remoteId;

    /**
     * @var Output
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Output", cascade={"persist"})
     */
    protected $output;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $parameters;

    /**
     * @var Parameters
     */
    private $parametersObject;

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
        $this->parametersObject = new Parameters($this->url, $this->getParametersArray());
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
        $parametersArray = json_decode($this->getParameters(), true);

        if (!is_array($parametersArray)) {
            $parametersArray = [];
        }

        return $parametersArray;
    }

    /**
     * @return Parameters
     */
    public function getParametersObject()
    {
        if (empty($this->parametersObject)) {
            $this->parametersObject = new Parameters($this->url, $this->getParametersArray());
        }

        return $this->parametersObject;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $stateName = str_replace('task-', '', $this->getState()->getName());
        $worker = $this->getWorker();

        $workerHostname = empty($worker)
            ? ''
            : $worker->getHostname();

        $taskData = [
            'id' => $this->getId(),
            'url' => $this->getUrl(),
            'state' => $stateName,
            'worker' => $workerHostname,
            'type' => $this->getType()->getName(),
        ];

        if (!empty($this->timePeriod) && !$this->timePeriod->isEmpty()) {
            $taskData['time_period'] = $this->timePeriod->jsonSerialize();
        }

        if (!empty($this->remoteId)) {
            $taskData['remote_id'] = $this->remoteId;
        }

        $output = $this->getOutput();

        if (!empty($output)) {
            $taskData['output'] = $output->jsonSerialize();
        }

        return $taskData;
    }
}
