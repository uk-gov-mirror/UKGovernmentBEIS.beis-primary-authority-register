<?php

namespace Drupal\par_enforcement_review_flows;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\par_data\Entity\ParDataEnforcementNotice;
use Drupal\par_flows\ParFlowException;
use Drupal\user\Entity\User;
use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;

trait ParFlowAccessTrait {

  /**
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object to be checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account being checked.
   */
  public function accessCallback(Route $route, RouteMatchInterface $route_match, AccountInterface $account, ParDataEnforcementNotice $par_data_enforcement_notice = NULL) {
    try {
      // Get a new flow negotiator that points the the route being checked for access.
      $access_route_negotiator = $this->getFlowNegotiator()->cloneFlowNegotiator($route_match);
    } catch (ParFlowException $e) {

    }
    
    // Steps 1 & 2 shouldn't be accessed if the enforcement notice has already been approved.
    if (!$par_data_enforcement_notice->inProgress() && $access_route_negotiator->getFlow()->getCurrentStep() <= 2) {
      // Set an error if this action has already been reviewed.
      $this->accessResult = AccessResult::forbidden('This action has already been reviewed.');
    }

    // If the user is not in the primary authority they should not be able to review an enforcement.
    $primary_authority = $par_data_enforcement_notice ? $par_data_enforcement_notice->getPrimaryAuthority(TRUE) : NULL;
    $user = User::load($account->id());
    if ($par_data_enforcement_notice && $primary_authority && !$this->getParDataManager()->isMember($primary_authority, $user)) {
      // Set an error if the user is not in the primary authority.
      $this->accessResult = AccessResult::forbidden('This user is not in the primary authority.');
    }

    return parent::accessCallback($route, $route_match, $account);
  }
}
