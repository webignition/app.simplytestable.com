<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class UserControllerTest extends BaseControllerJsonTestCase {
    

    public function testCreateAction() {
        $this->setupDatabase();
        
        $controller = $this->getUserController('createAction', array(
            'email' => 'user1@example.com',
            'password' => 'password1'            
        ));
        
        $response = $controller->createAction();
        
        $this->assertEquals(200, $response->getStatusCode());          
    }
    
}


