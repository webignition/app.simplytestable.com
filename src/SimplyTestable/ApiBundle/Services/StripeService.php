<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use Stripe;
use Stripe_Customer;

class StripeService {
    
    /**
     *
     * @var string
     */
    private $apiKey = '';
    
    
    /**
     * 
     * @param string $apiKey
     */
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    /**
     * 
     * @return string
     */
    public function getApiKey() {
        return $this->apiKey;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $plan
     */
    public function subscribe(User $user, Plan $plan) {
        Stripe::setApiKey($this->getApiKey());
        
//        var_dump("cp01", $user->getEmail(), $plan->getName());
//        exit();

        $response = Stripe_Customer::create(array(
            'email' => $user->getEmail(),
            'plan' => $plan->getName()
//          "description" => "Customer for test@example.com",
//          "card" => "tok_1XneNoJMrMXWTn" // obtained with Stripe.js
        ));
        
        var_dump($response);
        exit();
    }
    
}