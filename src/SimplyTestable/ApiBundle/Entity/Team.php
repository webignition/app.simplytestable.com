<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;
use SimplyTestable\ApiBundle\Entity\User;

/**
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="Team"
 * )
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class Team {
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
     * @var \SimplyTestable\ApiBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedUser")
     * @SerializerAnnotation\Expose
     */
    protected $leader;

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
     * Set leader
     *
     * @param User $leader
     * @return Team
     */
    public function setLeader(User $leader)
    {
        $this->leader = $leader;

        return $this;
    }

    /**
     * Get leader
     *
     * @return User
     */
    public function getLeader()
    {
        return $this->leader;
    }
}
