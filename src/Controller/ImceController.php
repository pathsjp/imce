<?php

namespace Drupal\imce\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\imce\Imce;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for imce routes.
 */
class ImceController extends ControllerBase {

  /**
   * Returns an administrative overview of Imce Profiles.
   */
  public function adminOverview(Request $request) {
    // Build the settings form first.(may redirect)
    $output['settings_form'] = $this->formBuilder()->getForm('Drupal\imce\Form\ImceSettingsForm') + ['#weight' => 10];
    // Buld profile list.
    $output['profile_list'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['imce-profile-list']],
      'title' => ['#markup' => '<h2>' . $this->t('Configuration Profiles') . '</h2>'],
      'list' => $this->entityTypeManager()->getListBuilder('imce_profile')->render(),
    ];
    return $output;
  }

  /**
   * Handles requests to /imce/{scheme} path.
   */
  public function page($scheme, Request $request) {
    return Imce::response($request, $this->currentUser(), $scheme);
  }

  /**
   * Checks access to /imce/{scheme} path.
   */
  public function checkAccess($scheme) {
    return AccessResult::allowedIf(Imce::access($this->currentUser(), $scheme));
  }

  /**
   * Handles request to /imce-get-uuid path.
   *
   * This will get the uuid for a Drupal file or if none exists for the uri,
   * will create a new File entity and return the UUID for the new one.
   *
   * Calls to this endpoint should have a get parameter for with the uri.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON object with the file uuid.
   */
  public function getUuid(Request $request) {
    // Get the current user.
    $user = \Drupal::currentUser();
    if (!$user->hasPermission('administration imce')) {
      return FALSE;
    }

    $uri = XSS::filter($request->query->get('uri'));
    $id = \Drupal::entityQuery('file')
      ->condition('uri', $uri)
      ->execute();
    if (!empty($id)) {
      $file = File::load(reset($id));
    }
    else {
      $file = File::create(['uri' => $uri]);
      $file->save();
    }
    return new JsonResponse([
      'uuid' => $file->uuid(),
    ]);
  }

}
