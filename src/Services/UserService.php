<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Doctrine\UserManager;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Util\TokenGenerator;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use FOS\UserBundle\Util\CanonicalizerInterface;
use App\Entity\User;

class UserService
{
    const PUBLIC_USER_EMAIL_ADDRESS = 'public@simplytestable.com';

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var CanonicalizerInterface
     */
    private $canonicalizer;

    /**
     * @param UserManagerInterface $userManager
     * @param EntityManagerInterface $entityManager
     * @param TokenGeneratorInterface $tokenGenerator
     * @param CanonicalizerInterface $canonicalizer
     */
    public function __construct(
        UserManagerInterface $userManager,
        EntityManagerInterface $entityManager,
        TokenGeneratorInterface $tokenGenerator,
        CanonicalizerInterface $canonicalizer
    ) {
        $this->userManager = $userManager;
        $this->tokenGenerator = $tokenGenerator;
        $this->canonicalizer = $canonicalizer;
    }

    /**
     * @return User
     */
    public function getPublicUser()
    {
        return $this->findUserByEmail(self::PUBLIC_USER_EMAIL_ADDRESS);
    }

    /**
     * @return User
     */
    public function getAdminUser()
    {
        /* @var User $user */
        $user = $this->userManager->findUserByUsername('admin');

        return $user;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function isPublicUser(User $user)
    {
        return $user->equals($this->getPublicUser());
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function isSpecialUser(User $user)
    {
        return $this->isPublicUser($user) || $user->equals($this->getAdminUser());
    }

    /**
     * @param string $email
     * @param string $password
     *
     * @return User
     */
    public function create($email, $password)
    {
        /* @var User $user */
        $user = $this->userManager->createUser();

        $user->setEmail($this->canonicalizer->canonicalize($email));
        $user->setEmailCanonical($this->canonicalizer->canonicalize($email));
        $user->setUsername($this->canonicalizer->canonicalize($email));
        $user->setPlainPassword($password);
        $user->setConfirmationToken($this->tokenGenerator->generateToken());

        $this->userManager->updateUser($user);

        return $user;
    }

    /**
     * @param string $emailCanonical
     *
     * @return bool
     */
    public function exists($emailCanonical)
    {
        $user = $this->userManager->findUserByEmail(
            $this->canonicalizer->canonicalize($emailCanonical)
        );

        return !empty($user);
    }

    /**
     * @param User $user
     *
     * @return string
     */
    public function getConfirmationToken(User $user)
    {
        $userConfirmationToken = $user->getConfirmationToken();

        if (empty($userConfirmationToken)) {
            $userConfirmationToken = $this->tokenGenerator->generateToken();

            $user->setConfirmationToken($userConfirmationToken);
            $this->userManager->updateUser($user);
        }

        return $userConfirmationToken;
    }

    /**
     * @param string $email
     *
     * @return User
     */
    public function findUserByEmail($email)
    {
        /* @var User $user */
        $user = $this->userManager->findUserByEmail($email);

        return $user;
    }

    /**
     * @param User $user
     */
    public function updateUser(User $user)
    {
        $this->userManager->updateUser($user);
    }

    /**
     * @param User $user
     */
    public function updatePassword(User $user)
    {
        $this->userManager->updatePassword($user);
    }

    /**
     * @param string $token
     *
     * @return User
     */
    public function findUserByConfirmationToken($token)
    {
        /* @var User $user */
        $user = $this->userManager->findUserByConfirmationToken($token);

        return $user;
    }
}
