<?php
namespace App\Entity\Team;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="TeamInvite",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="teamInvite_idx", columns={"team_id", "user_id"})}
 * )
 * @ORM\Entity(repositoryClass="App\Repository\TeamInviteRepository")
 */
class Invite implements \JsonSerializable
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Team\Team")
     * @ORM\JoinColumn(name="team_id", referencedColumnName="id", nullable=false)
     */
    protected $team;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $token;

    /**
     * @return int
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

    /**
     * @param $token
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
            'team' => $this->getTeam()->getName(),
            'user' => $this->getUser()->getEmail(),
            'token' => $this->getToken(),
        ];
    }
}
