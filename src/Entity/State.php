<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity
 */
class State
{
    /**
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;


    /**
     *
     * @var string
     *
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $name;

    public static function create(string $name): State
    {
        $state = new State();
        $state->name = $name;

        return $state;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @return string
     */
    public function __toString() {
        return $this->getName();
    }


    /**
     *
     * @param State $state
     * @return boolean
     */
    public function equals(State $state) {
        return $this->getName() == $state->getName();
    }
}