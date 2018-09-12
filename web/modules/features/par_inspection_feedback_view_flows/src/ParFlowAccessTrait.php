<?php

namespace Drupal\par_inspection_feedback_view_flows;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\par_data\Entity\ParDataInspectionFeedback;
use Drupal\par_flows\ParFlowException;
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
  public function accessCallback(Route $route, RouteMatchInterface $route_match, AccountInterface $account, ParDataInspectionFeedback $par_data_inspection_feedback = NULL) {
    try {
      $this->getFlowNegotiator()->setRoute($route_match);
      $this->getFlowDataHandler()->reset();
      $this->getFlowDataHandler()->setParameter('par_data_inspection_feedback', $par_data_inspection_feedback);
      $this->loadData();
    } catch (ParFlowException $e) {

    }

    return parent::accessCallback($route, $route_match, $account);
  }
}