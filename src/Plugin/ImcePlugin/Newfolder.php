<?php

/**
 * @file
 * Contains \Drupal\imce\Plugin\ImcePlugin\Newfolder.
 */

namespace Drupal\imce\Plugin\ImcePlugin;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\imce\Imce;
use Drupal\imce\ImcePluginBase;
use Drupal\imce\ImceFM;
use Drupal\imce\ImceFolder;

/**
 * Defines Imce New Folder plugin.
 *
 * @ImcePlugin(
 *   id = "newfolder",
 *   label = "New Folder",
 *   weight = -15,
 *   operations = {
 *     "newfolder" = "opNewfolder"
 *   }
 * )
 */
class Newfolder extends ImcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function permissionInfo() {
    return array(
      'create_subfolders' => $this->t('Create subfolders'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPage(array &$page, AccountProxyInterface $user) {
    if (Imce::permissionInPage('create_subfolders', $page)) {
      $page['#attached']['library'][] = 'imce/drupal.imce.newfolder';
    }
  }

  /**
   * Operation handler: newfolder.
   */
  public function opNewfolder(ImceFM $fm) {
    $folder = $fm->activeFolder;
    if (!$folder || !$folder->getPermission('create_subfolders')) {
      return;
    }
    // Create folder
    $name = $fm->getPost('newfolder');
    if (is_string($name) && $fm->validateFileName($name)) {
      // Check existence
      $uri = Imce::joinPaths($folder->getUri(), $name);
      if (file_exists($uri)) {
        drupal_set_message(t('%filename already exists.', array('%filename' => $name)), 'error');
      }
      // Create and add to js
      elseif (mkdir($uri, $fm->getConf('chmod_directory', 0775))) {
        $item = new ImceFolder($name);
        $folder->appendItem($item);
        $item->addToJs();
      }
    }
  }

}