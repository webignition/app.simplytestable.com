<?php

namespace App\Model\Job\Configuration;

use App\Entity\WebSite;
use App\Entity\Job\Type as JobType;
use App\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

class Values
{
    private $label;
    private $website;
    private $type;
    private $taskConfigurationCollection;
    private $parameters;

    public function __construct(
        string $label,
        WebSite $website,
        JobType $type,
        TaskConfigurationCollection $taskConfigurationCollection,
        ?string $parameters = null
    ) {
        $this->label = trim($label);
        $this->website = $website;
        $this->type = $type;
        $this->taskConfigurationCollection = $taskConfigurationCollection;
        $this->parameters = $parameters;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return WebSite
     */
    public function getWebsite() {
        return $this->website;
    }


    /**
     * @return bool
     */
    public function hasWebsite() {
        return !is_null($this->website);
    }


    /**
     * @param JobType $type
     * @return $this
     */
    public function setType(JobType $type) {
        $this->type = $type;
        return $this;
    }


    /**
     * @return JobType
     */
    public function getType() {
        return $this->type;
    }


    /**
     * @return bool
     */
    public function hasType() {
        return !is_null($this->type);
    }


    /**
     * @param TaskConfigurationCollection $taskConfigurationCollection
     * @return $this
     */
    public function setTaskConfigurationCollection(TaskConfigurationCollection $taskConfigurationCollection) {
        $this->taskConfigurationCollection = $taskConfigurationCollection;
        return $this;
    }


    /**
     * @return TaskConfigurationCollection
     */
    public function getTaskConfigurationCollection() {
        if (is_null($this->taskConfigurationCollection)) {
            $this->taskConfigurationCollection = new TaskConfigurationCollection();
        }

        return $this->taskConfigurationCollection;
    }


    /**
     * @return bool
     */
    public function hasTaskConfigurationCollection() {
        return !is_null($this->taskConfigurationCollection);
    }


    /**
     * @param string $parameters
     * @return $this
     */
    public function setParameters($parameters) {
        $this->parameters = $parameters;
        return $this;
    }


    /**
     * @return string
     */
    public function getParameters() {
        return $this->parameters;
    }


    /**
     * @return bool
     */
    public function hasParameters() {
        return !is_null($this->parameters);
    }
}
