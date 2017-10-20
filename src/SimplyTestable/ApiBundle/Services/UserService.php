<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Doctrine\UserManager;
use FOS\UserBundle\Util\TokenGenerator;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use SimplyTestable\ApiBundle\Repository\UserRepository;
use FOS\UserBundle\Util\CanonicalizerInterface;
use SimplyTestable\ApiBundle\Entity\User;

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
     * @var \Doctrine\ORM\EntityRepository
     */
    private $entityRepository;

    /**
     * @var CanonicalizerInterface
     */
    private $canonicalizer;

    /**
     * @param UserManager $userManager
     * @param EntityManagerInterface $entityManager
     * @param TokenGeneratorInterface $tokenGenerator
     * @param CanonicalizerInterface $canonicalizer
     */
    public function __construct(
        UserManager $userManager,
        EntityManagerInterface $entityManager,
        TokenGeneratorInterface $tokenGenerator,
        CanonicalizerInterface $canonicalizer
    ) {
        $this->userManager = $userManager;
        $this->tokenGenerator = $tokenGenerator;
        $this->canonicalizer = $canonicalizer;

        $this->entityRepository = $entityManager->getRepository(User::class);
    }

    /**
     * @return User
     */
    public function getPublicUser()
    {
        /* @var User $user */
        $user = $this->userManager->findUserByEmail(self::PUBLIC_USER_EMAIL_ADDRESS);

        return $user;
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
        if (!$user->hasConfirmationToken()) {
            $user->setConfirmationToken($this->tokenGenerator->generateToken());
        }

        $this->userManager->updateUser($user);

        return $user->getConfirmationToken();
    }

    /**
     * @return UserRepository
     */
    public function getEntityRepository()
    {
        return $this->entityRepository;
    }
}
