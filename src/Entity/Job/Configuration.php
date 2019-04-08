<?php

namespace App\Entity\Job;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\WebSite;
use App\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="JobConfiguration"
 * )
 */
class Configuration implements \JsonSerializable
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
     * @var string
     *
     * @ORM\Column(type="string", unique=false, nullable=false)
     */
    private $label;

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
     * @var Type
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Job\Type")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id", nullable=false)
     */
    private $type;

    /**
     * @var DoctrineCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Job\TaskConfiguration", mappedBy="jobConfiguration")
     */
    private $taskConfigurations = [];

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $parameters;

    public static function create(
        string $label,
        User $user,
        WebSite $website,
        Type $type,
        TaskConfigurationCollection $taskConfigurationCollection,
        string $parameters
    ): Configuration {
        $configuration = new Configuration();

        $configuration->label = $label;
        $configuration->user = $user;
        $configuration->website = $website;
        $configuration->type = $type;
        $configuration->setTaskConfigurationCollection($taskConfigurationCollection);
        $configuration->parameters = $parameters;

        return $configuration;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function update(string $label, WebSite $website, Type $type, string $parameters)
    {
        $this->label = $label;
        $this->website = $website;
        $this->type = $type;
        $this->parameters = $parameters;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return WebSite
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return Type
     */
    public function getType()
    {
        return $this->type;
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
    public function getLabel()
    {
        return $this->label;
    }

    public function setTaskConfigurationCollection(TaskConfigurationCollection $taskConfigurationCollection)
    {
        $taskConfigurations = new ArrayCollection();

        foreach ($taskConfigurationCollection as $taskConfiguration) {
            $taskConfiguration->setJobConfiguration($this);
            $taskConfigurations[] = $taskConfiguration;
        }

        $this->taskConfigurations = $taskConfigurations;
    }

    public function clearTaskConfigurationCollection()
    {
        $this->taskConfigurations = new ArrayCollection();
    }

    /**
     * @return TaskConfigurationCollection
     */
    public function getTaskConfigurationCollection()
    {
        $collection = new TaskConfigurationCollection();
        foreach ($this->taskConfigurations as $taskConfiguration) {
            $collection->add($taskConfiguration);
        }

        return $collection;
    }

    /**
     * @param Configuration $configuration
     *
     * @return bool
     */
    public function matches(Configuration $configuration)
    {
        if ($this->getWebsite() !== $configuration->getWebsite()) {
            return false;
        }

        if (!$this->getType()->equals($configuration->getType())) {
            return false;
        }

        if (!$this->getTaskConfigurationCollection()->equals($configuration->getTaskConfigurationCollection())) {
            return false;
        }

        return $this->parameters === $configuration->getParameters();
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $serializedTaskConfigurations = [];

        foreach ($this->taskConfigurations as $taskConfiguration) {
            $serializedTaskConfigurations[] = $taskConfiguration->jsonSerialize();
        }

        $jobConfigurationData = [
            'label' => $this->getLabel(),
            'user' => $this->getUser()->getEmail(),
            'website' => $this->getWebsite()->getCanonicalUrl(),
            'type' => $this->getType()->getName(),
            'task_configurations' => $serializedTaskConfigurations,
        ];

        if (!empty($this->parameters)) {
            $jobConfigurationData['parameters'] = $this->parameters;
        }

        return $jobConfigurationData;
    }
}
