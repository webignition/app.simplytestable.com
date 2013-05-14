<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Util\CanonicalizerInterface;
use SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest;
use SimplyTestable\ApiBundle\Entity\User;

class UserEmailChangeRequestService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest';
    
    /**
     *
     * @var string
     */
    private $tokenGeneratorClass;
    
    
    /**
     *
     * @var \FOS\UserBundle\Util\CanonicalizerInterface 
     */
    private $emailCanonicalizer;
    
    
    /**
     *
     * @var \FOS\UserBundle\Util\TokenGenerator 
     */
    private $tokenGenerator;
    
//    /**
//     *
//     * @var \Doctrine\ORM\EntityRepository
//     */
//    private $entityRepository;  
    
    /**
     *
     * @param EntityManager $entityManager
     * @param \FOS\UserBundle\Util\CanonicalizerInterface  $emailCanonicalizer
     */
    public function __construct(
            EntityManager $entityManager,
            \FOS\UserBundle\Util\CanonicalizerInterface  $emailCanonicalizer,
            $tokenGeneratorClass) {
        parent::__construct($entityManager);
        $this->emailCanonicalizer = $emailCanonicalizer;
        $this->tokenGeneratorClass = $tokenGeneratorClass;
    }
    
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    /**
     * Canonicalizes an email
     *
     * @param string $email
     * @return string
     */
    public function canonicalizeEmail($email)
    {
        return $this->emailCanonicalizer->canonicalize($email);
    } 
    
    /**
     * 
     * @param string $new_email
     * @return UserEmailChangeRequest
     */    
    public function findByNewEmail($new_email) {
        return $this->getEntityRepository()->findOneByNewEmail($new_email);
    }
    
    /**
     * 
     * @param string $new_email
     * @return boolean
     */    
    public function hasForNewEmail($new_email) {
        return !is_null($this->getEntityRepository()->findOneByNewEmail($new_email));
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return UserEmailChangeRequest
     */
    public function findByUser(User $user) {
        return $this->getEntityRepository()->findOneByUser($user);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return boolean
     */
    public function hasForUser(User $user) {
        return !is_null($this->findByUser($user));
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return boolean
     */
    public function removeForUser(User $user) {
        if ($this->hasForUser($user)) {
            $this->getEntityManager()->remove($this->findByUser($user));
            $this->getEntityManager()->flush();
        }

        return true;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @param string $new_email
     * @return UserEmailChangeRequest
     */
    public function create(User $user, $new_email) {
        if ($this->hasForUser($user)) {
            return $this->findByUser($user);
        }
        
        $emailChangeRequest = new UserEmailChangeRequest();
        $emailChangeRequest->setNewEmail($new_email);
        $emailChangeRequest->setToken($this->getTokenGenerator()->generateToken());
        $emailChangeRequest->setUser($user);
        
        return $this->persistAndFlush($emailChangeRequest);
    }

    

//
//    
//    
//    /**
//     * 
//     * @param string $email
//     * @param string $password
//     * @return \SimplyTestable\ApiBundle\Entity\User
//     */
//    public function create($email, $password) {
//        if ($this->exists($email)) {
//            return false;
//        }        
//        
//        $user = $this->createUser();
//        $user->setEmail($this->canonicalizeEmail($email));
//        $user->setEmailCanonical($this->canonicalizeEmail($email));
//        $user->setUsername($this->canonicalizeUsername($email));
//        $user->setPlainPassword($password);
//        $user->setConfirmationToken($this->getTokenGenerator()->generateToken());            
//
//        $this->updateUser($user);
//        
//        return $user;
//
//    }
//    
//    
//    /**
//     * 
//     * @param string $emailCanonical
//     * @return boolean
//     */
//    public function exists($emailCanonical) {        
//        return !is_null($this->findUserByEmail($this->canonicalizeEmail($emailCanonical)));
//    }
    
    
    
    
    
//    /**
//     * 
//     * @param \SimplyTestable\ApiBundle\Entity\User $user
//     * @param string $new_email
//     * @return string
//     */
//    public function getConfirmationToken(User $user, $new_email) {
//        if (!$user->hasConfirmationToken()) {
//            $user->setConfirmationToken($this->getTokenGenerator()->generateToken());
//        }
//        
//        $this->updateUser($user);
//        return $user->getConfirmationToken();
//    }
    
    
    /**
     * 
     * @return \FOS\UserBundle\Util\TokenGenerator
     */
    private function getTokenGenerator() {
        if (is_null($this->tokenGenerator)) {
            $this->tokenGenerator = new $this->tokenGeneratorClass;            
        }
        
        return $this->tokenGenerator;
    } 
    
    
    /**
     *
     * @param WebSite $job
     * @return WebSite
     */
    private function persistAndFlush(UserEmailChangeRequest $emailChangeRequest) {
        $this->getEntityManager()->persist($emailChangeRequest);
        $this->getEntityManager()->flush();
        return $emailChangeRequest;
    }      
    
    
//    /**
//     *
//     * @return \SimplyTestable\ApiBundle\Repository\UserRepository
//     */
//    public function getEntityRepository() {
//        if (is_null($this->entityRepository)) {            
//            $this->entityRepository = $this->objectManager->getRepository('SimplyTestable\ApiBundle\Entity\User');
//        }
//        
//        return $this->entityRepository;
//    } 
}