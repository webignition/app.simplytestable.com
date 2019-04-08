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

    public function getWebsite(): WebSite
    {
        return $this->website;
    }

    public function getType(): JobType
    {
        return $this->type;
    }

    public function getTaskConfigurationCollection()
    {
        return $this->taskConfigurationCollection;
    }

    public function getParameters(): ?string
    {
        return $this->parameters;
    }
}
