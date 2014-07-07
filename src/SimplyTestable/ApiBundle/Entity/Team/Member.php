<?php
namespace SimplyTestable\ApiBundle\Entity\Team;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="TeamMember"
 * )
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class Member {
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
     */
    protected $user;



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
     * Set team
     *
     * @param \SimplyTestable\ApiBundle\Entity\Team\Team $team
     * @return Member
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
     * @return Member
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
}
