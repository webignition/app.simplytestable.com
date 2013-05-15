<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * @ORM\Entity
 * 
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class UserEmailChangeRequest
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
     * @var \SimplyTestable\ApiBundle\Entity\User
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;
    
    
    /**
     *
     * @var string 
     * 
     * @ORM\Column(type="string", unique=true)
     * @SerializerAnnotation\Expose 
     */    
    protected $newEmail;
    
    
    /**
     *
     * @var string 
     * 
     * @ORM\Column(type="string", unique=true)
     * @SerializerAnnotation\Expose 
     */    
    protected $token;
    
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedUser() {
        return $this->getUser()->getUsername();
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
     * Set newEmail
     *
     * @param string $newEmail
     * @return UserEmailChangeRequest
     */
    public function setNewEmail($newEmail)
    {
        $this->newEmail = $newEmail;
    
        return $this;
    }

    /**
     * Get newEmail
     *
     * @return string 
     */
    public function getNewEmail()
    {
        return $this->newEmail;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return UserEmailChangeRequest
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
     * Set user
     *
     * @param SimplyTestable\ApiBundle\Entity\User $user
     * @return UserEmailChangeRequest
     */
    public function setUser(\SimplyTestable\ApiBundle\Entity\User $user)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return SimplyTestable\ApiBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }
}