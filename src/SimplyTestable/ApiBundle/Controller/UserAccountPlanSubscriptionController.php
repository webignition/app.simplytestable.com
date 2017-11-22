<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\StripeService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserService;
use Stripe\Error\Card as StripeCardError;
use Stripe\Error\Authentication as StripeAuthenticationError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserAccountPlanSubscriptionController
{
    /**
     * @var ApplicationStateService $applicationStateService
     */
    private $applicationStateService;

    /**
     * @var UserService $userService
     */
    private $userService;

    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var AccountPlanService
     */
    private $accountPlanService;

    /**
     * @var StripeService
     */
    private $stripeService;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param UserService $userService
     * @param UserAccountPlanService $userAccountPlanService
     * @param AccountPlanService $accountPlanService
     * @param StripeService $stripeService
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        UserService $userService,
        UserAccountPlanService $userAccountPlanService,
        AccountPlanService $accountPlanService,
        StripeService $stripeService
    ) {
        $this->applicationStateService = $applicationStateService;
        $this->userService = $userService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->accountPlanService = $accountPlanService;
        $this->stripeService = $stripeService;
    }

    /**
     * @param UserInterface|User $user
     * @param string $email_canonical
     * @param string $plan_name
     * @return Response
     */
    public function subscribeAction(UserInterface $user, $email_canonical, $plan_name)
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        if ($this->userService->isPublicUser($user)) {
            throw new BadRequestHttpException();
        }

        if ($email_canonical !== $user->getEmail()) {
            throw new BadRequestHttpException();
        }

        $plan = $this->accountPlanService->get($plan_name);
        if (empty($plan)) {
            throw new BadRequestHttpException();
        }

        try {
            $this->userAccountPlanService->subscribe($user, $plan);
        } catch (StripeAuthenticationError $stripeAuthenticationError) {
            return Response::create('', 403);
        } catch (StripeCardError $stripeCardError) {
            $this->userAccountPlanService->removeCurrentForUser($user);
            return Response::create('', 400, [
                'X-Stripe-Error-Message' => $stripeCardError->getMessage(),
                'X-Stripe-Error-Param' => $stripeCardError->param,
                'X-Stripe-Error-Code' => $stripeCardError->getCode()
            ]);
        } catch (UserAccountPlanServiceException $userAccountPlanServiceException) {
            if ($userAccountPlanServiceException->isUserIsTeamMemberException()) {
                return Response::create('', 400, [
                    'X-Error-Message' => 'User is a team member',
                    'X-Error-Code' => $userAccountPlanServiceException->getCode()
                ]);
            }
        }

        return new Response();
    }

    /**
     * @param UserInterface|User $user
     * @param string $email_canonical
     * @param string $stripe_card_token
     *
     * @return Response
     */
    public function associateCardAction(UserInterface $user, $email_canonical, $stripe_card_token)
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        if ($this->userService->isPublicUser($user)) {
            throw new BadRequestHttpException();
        }

        if ($email_canonical !== $user->getEmail()) {
            throw new BadRequestHttpException();
        }

        $isValidStripeCardToken = preg_match('/tok_[a-z0-9]{14}/i', $stripe_card_token) > 0;
        if (!$isValidStripeCardToken) {
            throw new BadRequestHttpException();
        }

        $userAccountPlan = $this->userAccountPlanService->getForUser($user);
        if (empty($userAccountPlan->getStripeCustomer())) {
            throw new BadRequestHttpException();
        }

        try {
            $this->stripeService->updateCustomer($userAccountPlan, [
                'card' => $stripe_card_token
            ]);
        } catch (StripeCardError $stripeCardError) {
            return Response::create('', 400, [
                'X-Stripe-Error-Message' => $stripeCardError->getMessage(),
                'X-Stripe-Error-Param' => $stripeCardError->param,
                'X-Stripe-Error-Code' => $stripeCardError->getCode()
            ]);
        }

        return new Response();
    }
}
