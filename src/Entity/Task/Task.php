<?php

namespace App\Entity\Task;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Job\Job;
use App\Entity\State;
use App\Entity\TimePeriod;
use App\Model\Parameters;

/**
 *
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="App\Repository\TaskRepository")
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
    const STATE_EXPIRED = 'task-expired';

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
     * @ORM\ManyToOne(targetEntity="App\Entity\Job\Job", inversedBy="tasks")
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
     * @ORM\ManyToOne(targetEntity="App\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     */
    protected $state;

    /**
     * @var TaskType
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Task\TaskType")
     * @ORM\JoinColumn(name="tasktype_id", referencedColumnName="id", nullable=false)
     */
    protected $type;

    /**
     * @var TimePeriod
     *
     * @ORM\OneToOne(targetEntity="App\Entity\TimePeriod", cascade={"persist", "remove"})
     */
    protected $timePeriod;

    /**
     * @var Output
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Task\Output", cascade={"persist"})
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

    /**
     * @return State
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param TaskType $type
     */
    public function setType(TaskType $type)
    {
        $this->type = $type;
    }

    /**
     * @return TaskType
     */
    public function getType()
    {
        return $this->type;
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
        $this->createParametersObject();
    }

    /**
     * @return string
     */
    public function getParametersString()
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function getParametersHash()
    {
        return md5($this->parameters);
    }

    /**
     * @return Parameters
     */
    public function getParameters()
    {
        if (empty($this->parametersObject)) {
            $this->createParametersObject();
        }

        return $this->parametersObject;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $stateName = str_replace('task-', '', (string) $this->getState());

        $taskData = [
            'id' => $this->getId(),
            'url' => $this->getUrl(),
            'state' => $stateName,
            'type' => $this->getType()->getName(),
        ];

        if (!empty($this->timePeriod) && !$this->timePeriod->isEmpty()) {
            $taskData['time_period'] = $this->timePeriod->jsonSerialize();
        }

        $output = $this->getOutput();

        if (!empty($output)) {
            $taskData['output'] = $output->jsonSerialize();
        }

        return $taskData;
    }

    private function createParametersObject()
    {
        $parametersArray = json_decode($this->parameters, true);

        if (!is_array($parametersArray)) {
            $parametersArray = [];
        }

        $this->parametersObject = new Parameters($parametersArray);
    }
}
