<?php
namespace App\Entity\Job;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\WebSite;
use App\Entity\State;
use App\Entity\Task\TaskType;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="JobTaskTypeOptions"
 * )
 */
class TaskTypeOptions
{
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Job\Job", inversedBy="taskTypeOptions")
     * @ORM\JoinColumn(name="job_id", referencedColumnName="id", nullable=false)
     */
    protected $job;

    /**
     * @var TaskType
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Task\TaskType")
     * @ORM\JoinColumn(name="tasktype_id", referencedColumnName="id", nullable=false)
     */
    protected $taskType;

    /**
     * @var DoctrineCollection
     *
     * @ORM\Column(type="array", name="options", nullable=false)
     */
    protected $options;

    public function __construct()
    {
        $this->options = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
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
     * @param TaskType $taskType
     */
    public function setTaskType(TaskType $taskType)
    {
        $this->taskType = $taskType;
    }

    /**
     * @return TaskType
     */
    public function getTaskType()
    {
        return $this->taskType;
    }

    /**
     * @return int
     */
    public function getOptionCount()
    {
        return count($this->getOptions());
    }

    /**
     * @param string $optionName
     *
     * @return mixed
     */
    public function getOption($optionName)
    {
        $options = $this->getOptions();
        return (isset($options[$optionName])) ? $options[$optionName] : null;
    }

    /**
     * @param string $optionName
     * @return bool
     */
    public function hasOption($optionName)
    {
        return !is_null($this->getOption($optionName));
    }
}
