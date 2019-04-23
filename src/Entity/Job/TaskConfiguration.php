<?php

namespace App\Entity\Job;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Task\TaskType;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="JobTaskConfiguration"
 * )
 */
class TaskConfiguration implements \JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Configuration
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Job\Configuration", inversedBy="taskConfigurations")
     * @ORM\JoinColumn(name="jobconfiguration_id", referencedColumnName="id", nullable=false)
     */
    protected $jobConfiguration;

    /**
     * @var TaskType
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Task\TaskType")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id", nullable=true)
     */
    protected $type;

    /**
     * @var array
     *
     * @ORM\Column(type="array", name="options", nullable=false)
     */
    protected $options;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" = true})
     */
    private $isEnabled = true;

    public function __construct()
    {
        $this->options = [];
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @param Configuration $jobConfiguration
     */
    public function setJobConfiguration(Configuration $jobConfiguration)
    {
        $this->jobConfiguration = $jobConfiguration;
    }

    /**
     * @return Configuration
     */
    public function getJobConfiguration()
    {
        return $this->jobConfiguration;
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
     *
     * @return bool
     */
    public function hasOption($optionName)
    {
        return !is_null($this->getOption($optionName));
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param TaskConfiguration $taskConfiguration
     *
     * @return bool
     */
    public function hasMatchingTypeAndOptions(TaskConfiguration $taskConfiguration)
    {
        if ($this->getType()->getName() != $taskConfiguration->getType()->getName()) {
            return false;
        }

        if ($this->getOptionCount() != $taskConfiguration->getOptionCount()) {
            return false;
        }

        if ($this->getOptions() != $taskConfiguration->getOptions()) {
            return false;
        }

        return true;
    }

    /**
     * @param $isEnabled
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;
    }

    /**
     * @return bool
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'type' => $this->getType()->getName(),
            'options' => $this->getOptions(),
            'is_enabled' => $this->getIsEnabled(),
        ];
    }
}
