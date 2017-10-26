<?php
namespace SimplyTestable\ApiBundle\Entity\Team;

use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\ApiBundle\Entity\User;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="TeamMember",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="teamMember_idx", columns={"team_id", "user_id"})}
 * )
 * @ORM\Entity(repositoryClass="SimplyTestable\ApiBundle\Repository\TeamMemberRepository")
 */
class Member
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
     * @var Team
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Team\Team")
     * @ORM\JoinColumn(name="team_id", referencedColumnName="id", nullable=false)
     */
    protected $team;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Team $team
     */
    public function setTeam(Team $team)
    {
        $this->team = $team;
    }

    public function clear()
    {
        $this->team = null;
        $this->user = null;
    }

    /**
     * @return Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
