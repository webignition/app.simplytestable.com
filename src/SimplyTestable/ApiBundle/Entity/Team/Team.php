<?php
namespace SimplyTestable\ApiBundle\Entity\Team;

use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\ApiBundle\Entity\User;

/**
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="Team"
 * )
 * @ORM\Entity(repositoryClass="SimplyTestable\ApiBundle\Repository\TeamRepository")
 */
class Team implements \JsonSerializable
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     * @ORM\JoinColumn(name="leader_id", referencedColumnName="id", nullable=false)
     */
    protected $leader;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param User $leader
     */
    public function setLeader(User $leader)
    {
        $this->leader = $leader;
    }

    /**
     * @return User
     */
    public function getLeader()
    {
        return $this->leader;
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
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'leader' => $this->getLeader()->getEmail(),
            'name' => $this->getName(),
        ];
    }
}
