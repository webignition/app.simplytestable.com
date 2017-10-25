<?php
namespace SimplyTestable\ApiBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
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

    /**
     * @param User $user
     *
     * @return bool
     */
    public function equals(User $user)
    {
        return $this->getEmailCanonical() == $user->getEmailCanonical();
    }
}
