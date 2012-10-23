<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Doctrine\UserManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use FOS\UserBundle\Util\CanonicalizerInterface;
use Doctrine\Common\Persistence\ObjectManager;

class UserService extends UserManager {
    
    const PUBLIC_USER_EMAIL_ADDRESS = 'public@simplytestable.com';
    
    /**
     *
     * @var string
     */
    private $tokenGeneratorClass;
    
    /**
     *
     * @var \FOS\UserBundle\Util\TokenGenerator 
     */
    private $tokenGenerator;
    
    /**
     * Constructor.
     *
     * @param EncoderFactoryInterface $encoderFactory
     * @param CanonicalizerInterface  $usernameCanonicalizer
     * @param CanonicalizerInterface  $emailCanonicalizer
     * @param ObjectManager          $om
     * @param string                 $class
     * @param string                 $tokenGeneratorClass
     */
    public function __construct(EncoderFactoryInterface $encoderFactory, CanonicalizerInterface $usernameCanonicalizer, CanonicalizerInterface $emailCanonicalizer, ObjectManager $om, $class, $tokenGeneratorClass)
    {
        parent::__construct($encoderFactory, $usernameCanonicalizer, $emailCanonicalizer, $om, $class);
        $this->tokenGeneratorClass = $tokenGeneratorClass;
    }    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\User
     */
    public function getPublicUser() {
        return $this->findUserByEmail(self::PUBLIC_USER_EMAIL_ADDRESS);
    }
    
    
    /**
     * 
     * @param string $email
     * @param string $password
     * @return \SimplyTestable\ApiBundle\Entity\User
     */
    public function create($email, $password) {
        if ($this->exists($email)) {
            return false;
        }        
        
        $user = $this->createUser();
        $user->setEmail($this->canonicalizeEmail($email));
        $user->setEmailCanonical($this->canonicalizeEmail($email));
        $user->setUsername($this->canonicalizeUsername($email));
        $user->setPlainPassword($password);
        $user->setConfirmationToken($this->getTokenGenerator()->generateToken());            

        $this->updateUser($user);
        
        return $user;

    }
    
    
    /**
     * 
     * @param string $emailCanonical
     * @return boolean
     */
    public function exists($emailCanonical) {        
        return !is_null($this->findUserByEmail($this->canonicalizeEmail($emailCanonical)));
    }
    
    
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
}