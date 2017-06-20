<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;

class UserPasswordResetController extends UserController
{

    public function __construct() {
        $this->setInputDefinitions(array(
            'resetPasswordAction' => new InputDefinition(array(
                new InputArgument('password', InputArgument::REQUIRED, 'Choice of new user password')
            ))
        ));

        $this->setRequestTypes(array(
            'resetPasswordAction' => \Guzzle\Http\Message\Request::POST
        ));
    }


    public function resetPasswordAction($token) {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $user = $this->getUserService()->findUserByConfirmationToken($token);
        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }

        if (!$user->isEnabled()) {
            $this->getUserManipulator()->activate($user->getUsername());
        }

        $user->setPlainPassword(rawurldecode($this->getArguments('resetPasswordAction')->get('password')));
        $user->setConfirmationToken(null);
        $user->setPasswordRequestedAt(null);

        $this->getUserService()->updateUser($user);

        return new \Symfony\Component\HttpFoundation\Response();
    }

}
