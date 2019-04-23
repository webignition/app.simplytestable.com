<?php

namespace App\Entity\Task;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="TaskType")
 */
class Type implements \JsonSerializable
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
     * @var string
     *
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     */
    protected $description;

    /**
     *
     * @var bool
     * @ORM\Column(type="boolean", name="selectable", nullable=false)
     */
    protected $selectable = false;

    public function __construct()
    {
        $this->selectable = false;
    }


    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param bool $selectable
     */
    public function setSelectable($selectable)
    {
        $this->selectable = $selectable;
    }

    /**
     * @return bool
     */
    public function getSelectable()
    {
        return $this->selectable;
    }

    /**
     * @param Type $taskType
     *
     * @return bool
     */
    public function equals(Type $taskType)
    {
        return $this->getName() == $taskType->getName();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
        ];
    }
}
