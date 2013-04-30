<?php
namespace SimplyTestable\ApiBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 * @ORM\Entity(repositoryClass="SimplyTestable\ApiBundle\Repository\UserRepository")
 * 
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    public function __construct()
    {
        parent::__construct();
        // your own logic
    }
    
    
    /**
     *
     * @param User $user
     * @return boolean
     */
    public function equals(User $user) {
        return $this->getEmailCanonical() == $user->getEmailCanonical();
    }
    
    /**
     * 
     * @return boolean
     */
    public function hasConfirmationToken() {
        return !is_null($this->getConfirmationToken());
    }
}