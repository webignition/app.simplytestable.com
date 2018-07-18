<?php

namespace AppBundle\Model\Job\Configuration;

use AppBundle\Entity\WebSite;
use AppBundle\Entity\Job\Type as JobType;
use AppBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

class Values {

    /**
     *
     * @var string
     */
    private $label;


    /**
     *
     * @var WebSite
     */
    private $website;


    /**
     *
     * @var JobType
     */
    private $type;


    /**
     *
     * @var TaskConfigurationCollection
     */
    private $taskConfigurationCollection;


    /**
     *
     * @var string
     */
    private $parameters;


    /**
     * @param $label
     * @return $this
     */
    public function setLabel($label) {
        if (is_null($label)) {
            $this->label = null;
        } else {
            $this->label = trim($label);
        }

        return $this;
    }


    /**
     * @return string|null
     */
    public function getLabel() {
        return $this->label;
    }


    /**
     * @return bool
     */
    public function hasLabel() {
        return !is_null($this->label);
    }


    /**
     * @return bool
     */
    public function hasEmptyLabel() {
        return !$this->hasLabel() || $this->getLabel() == '';
    }


    /**
     * @return bool
     */
    public function hasNonEmptyLabel() {
        return !$this->hasEmptyLabel();
    }


    /**
     * @param WebSite $website
     * @return $this
     */
    public function setWebsite(Website $website ) {
        $this->website = $website;
        return $this;
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
