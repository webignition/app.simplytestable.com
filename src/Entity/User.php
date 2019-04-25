<?php

namespace App\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser implements \JsonSerializable
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

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'email' => $this->getEmailCanonical(),
        ];
    }
}
