<?php
namespace SimplyTestable\ApiBundle\Entity\Team;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 *
 * @ORM\Entity
 * @ORM\Table(name="TeamInvite",uniqueConstraints={@ORM\UniqueConstraint(name="teamInvite_idx", columns={"team_id", "user_id"})},indexes={@ORM\Index(name="token_idx", columns={"token"})})
 * @SerializerAnnotation\ExclusionPolicy("all")
 * @ORM\Entity(repositoryClass="SimplyTestable\ApiBundle\Repository\TeamInviteRepository")
 */
class Invite {
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
     * @var \SimplyTestable\ApiBundle\Entity\Team\Team
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Team\Team")
     * @ORM\JoinColumn(name="team_id", referencedColumnName="id", nullable=false)
     */
    protected $team;

    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedUser")
     * @SerializerAnnotation\Expose
     */
    protected $user;


    /**
     *
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @SerializerAnnotation\Expose
     */
    protected $token;

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
     * Set token
     *
     * @param string $token
     * @return Invite
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set team
     *
     * @param \SimplyTestable\ApiBundle\Entity\Team\Team $team
     * @return Invite
     */
    public function setTeam(\SimplyTestable\ApiBundle\Entity\Team\Team $team)
    {
        $this->team = $team;

        return $this;
    }

    /**
     * Get team
     *
     * @return \SimplyTestable\ApiBundle\Entity\Team\Team 
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * Set user
     *
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return Invite
     */
    public function setUser(\SimplyTestable\ApiBundle\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \SimplyTestable\ApiBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }


    /**
     *
     * @return string
     */
    public function getPublicSerializedUser() {
        return $this->getUser()->getUsername();
    }
}
