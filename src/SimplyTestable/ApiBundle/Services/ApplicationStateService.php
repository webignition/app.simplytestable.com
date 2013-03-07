<?php
namespace SimplyTestable\ApiBundle\Services;


class ApplicationStateService {
    
    const DEFAULT_STATE = 'active';
    
    private $allowedStates = array(
        'active',
        'maintenance-read-only'
    );
    
    /**
     *
     * @var string
     */
    private $stateResourcePath;
    
    
    /**
     *
     * @var string
     */
    private $state;
    
    
    /**
     * 
     * @return string
     */
    public function getState() {
        if (is_null($this->state)) {
            if (file_exists($this->stateResourcePath)) {
                $this->state = trim(file_get_contents($this->stateResourcePath));
            }
            
            if (!in_array($this->state, $this->allowedStates)) {
                $this->state = self::DEFAULT_STATE;
            }
        }
        
        return $this->state;        
    }
    
    
    /**
     * 
     * @param string $stateResourcePath
     */
    public function setStateResourcePath($stateResourcePath) {
        $this->stateResourcePath = $stateResourcePath;
    }
    
    
}