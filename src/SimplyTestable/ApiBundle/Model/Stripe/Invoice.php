<?php

namespace SimplyTestable\ApiBundle\Model\Stripe;

class Invoice {
    
    
    /**
     *
     * @var \stdClass
     */
    private $data;
    
    
    public function __construct(\stdClass $data) {
        $this->data = $data;
    }
    
    
    /**
     * 
     * @return string
     */
    public function getId() {
        return $this->data->id;
    }
    
    
    /**
     * 
     * @return int
     */
    public function getTotal() {
        return $this->data->total;
    }
    
    
    /**
     * 
     * @return int
     */
    public function getAmountDue() {
        return $this->data->amount_due;
    }
    
    
    /**
     * 
     * @return array
     */
    public function getLinesSummary() {
        $linesSummary = array();
        
        foreach ($this->data->lines->data as $line) {
            
            $linesSummary[] = array(
                'proration' => $line->proration,
                'plan_name' => $line->plan->name
            );
        }
        
        return $linesSummary;
    }
    
    
    /**
     * 
     * @return int
     */
    public function getNextPaymentAttempt() {
        return $this->data->next_payment_attempt;
    }
    
}