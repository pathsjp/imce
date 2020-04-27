<?php

namespace Drupal\imce\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ImceBrowserController.
 */
class ImceBrowserController extends ControllerBase {

  /**
   * Browser.
   *
   * @return string
   *   Return Hello string.
   */
  public function browser() {
    $render['iframeImce'] = [
      '#type' => 'inline_template',
      '#template' => '<iframe class="imce-browser" src="{{ url }}"></iframe>',
      '#context' => [
        'url' => '/imce',
      ],
    ];

    $render['#attached']['library'][] = 'imce/drupal.imce.admin';

    return $render;
  }

}
