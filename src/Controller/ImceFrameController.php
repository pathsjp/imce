<?php

namespace Drupal\imce\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\imce\Imce;

/**
 * Class ImceLinkTaskController.
 */
class ImceFrameController extends ControllerBase {

  /**
   * Browser Page.
   *
   * @return string
   *   Return the IMCE file manager in a frame.
   */
  public function page() {
    $render['iframe'] = [
      '#type' => 'inline_template',
      '#template' => '<iframe class="imce-browser" src="{{ url }}"></iframe>',
      '#context' => [
        'url' => '/imce',
      ],
    ];
    $render['#attached']['library'][] = 'imce/drupal.imce.admin';
    return $render;
  }

  /**
   * Checks access to /user/{user}/imce path.
   */
  public function checkAccess() {
    $user_imce_profile = Imce::userProfile();
    return AccessResult::allowedIf(Imce::access($this->currentUser()) && $user_imce_profile->getConf('usertab'));
  }

}
