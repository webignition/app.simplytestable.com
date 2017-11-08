<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Util\CanonicalizerInterface;
use FOS\UserBundle\Util\TokenGenerator;
use SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest;
use SimplyTestable\ApiBundle\Entity\User;

class UserEmailChangeRequestService extends EntityService
{
    /**
     * @var string
     */
    private $tokenGeneratorClass;

    /**
     * @var CanonicalizerInterface
     */
    private $emailCanonicalizer;

    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * @param EntityManager $entityManager
     * @param CanonicalizerInterface $emailCanonicalizer
     * @param string $tokenGeneratorClass
     */
    public function __construct(
        EntityManager $entityManager,
        CanonicalizerInterface $emailCanonicalizer,
        $tokenGeneratorClass
    ) {
        parent::__construct($entityManager);

        $this->emailCanonicalizer = $emailCanonicalizer;
        $this->tokenGeneratorClass = $tokenGeneratorClass;
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return UserEmailChangeRequest::class;
    }

    /**
     * @param User $user
     *
     * @return UserEmailChangeRequest
     */
    public function findByUser(User $user)
    {
        return $this->getEntityRepository()->findOneByUser($user);
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function hasForUser(User $user)
    {
        return !is_null($this->findByUser($user));
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function removeForUser(User $user)
    {
        if ($this->hasForUser($user)) {
            $this->getManager()->remove($this->findByUser($user));
            $this->getManager()->flush();
        }

        return true;
    }

    /**
     * @param User $user
     * @param string $new_email
     *
     * @return UserEmailChangeRequest
     */
    public function create(User $user, $new_email)
    {
        if ($this->hasForUser($user)) {
            return $this->findByUser($user);
        }

        $emailChangeRequest = new UserEmailChangeRequest();
        $emailChangeRequest->setNewEmail($new_email);
        $emailChangeRequest->setToken($this->getTokenGenerator()->generateToken());
        $emailChangeRequest->setUser($user);

        return $this->persistAndFlush($emailChangeRequest);
    }

    /**
     * @return TokenGenerator
     */
    private function getTokenGenerator()
    {
        if (is_null($this->tokenGenerator)) {
            $this->tokenGenerator = new $this->tokenGeneratorClass;
        }

        return $this->tokenGenerator;
    }

    /**
     * @param UserEmailChangeRequest $emailChangeRequest
     *
     * @return UserEmailChangeRequest
     */
    private function persistAndFlush(UserEmailChangeRequest $emailChangeRequest)
    {
        $this->getManager()->persist($emailChangeRequest);
        $this->getManager()->flush();
        return $emailChangeRequest;
    }
}
