<?php
namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\Task\Task;

/**
 * @ORM\Entity
 */
class Worker implements \JsonSerializable
{
    const STATE_ACTIVE = 'worker-active';
    const STATE_UNACTIVATED = 'worker-unactivated';
    const STATE_DELETED = 'worker-deleted';
    const STATE_OFFLINE = 'worker-offline';

    const STATE_NAME_PREFIX = 'worker-';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $hostname;

    /**
     * @var DoctrineCollection
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Task\Task", mappedBy="worker")
     */
    protected $tasks;

    /**
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=true)
     */
    protected $state;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=false, nullable=true)
     */
    private $token;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $hostname
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * @param Task $tasks
     */
    public function addTask(Task $tasks)
    {
        $this->tasks[] = $tasks;
    }

    /**
     * @param Task $tasks
     */
    public function removeTask(Task $tasks)
    {
        $this->tasks->removeElement($tasks);
    }

    /**
     * Get tasks
     *
     * @return DoctrineCollection
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'hostname' => $this->getHostname(),
            'state' => str_replace(self::STATE_NAME_PREFIX, '', $this->getState()->getName()),
        ];
    }
}
