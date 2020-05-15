<?php

namespace Drupal\imce\Plugin\ImcePlugin;

use Drupal\imce\Imce;
use Drupal\imce\ImcePluginBase;
use Drupal\imce\ImceFM;
use Drupal\Core\File\Exception\FileException;

/**
 * Defines Imce Dropfile plugin.
 *
 * @ImcePlugin(
 *   id = "dropfile",
 *   label = "Drop File",
 *   weight = -5,
 *   operations = {
 *     "dropfile" = "opDropfile"
 *   }
 * )
 */
class Dropfile extends ImcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function permissionInfo() {
    return [
      'create_subfolders' => $this->t('Create subfolders'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPage(array &$page, ImceFM $fm) {
    if ($fm->hasPermission('create_subfolders')) {
      $page['#attached']['library'][] = 'imce/drupal.imce.newfolder';
    }
  }

  /**
   * Operation handler: dropfile.
   */
  public function opDropfile(ImceFM $fm) {
    $folder = $fm->activeFolder;
    if (!$folder || !$folder->getPermission('create_subfolders')) {
      return;
    }

    $fileUri = $fm->getPost('fileUri');
    $fileName = $fm->getPost('fileName');
    $filePath = $fm->getPost('filePath');
    $folderPath = $fm->getPost('folderPath');

    // Check and get folder destination.
    $folderDestination = $fm->checkFolder($folderPath);

    if ($folderDestination->type == "folder") {
      $uri = Imce::joinPaths($folderDestination->getUri(), $fileName);
      if (file_exists($uri)) {
        $fm->setMessage($this->t('%filename already exists in the folder %folderUrl.', ['%filename' => $fileName, '%folderUrl' => $folderDestination->getUri()]));
      }
      else {
        try {
          $file_system = \Drupal::service('file_system');
          $uri = $file_system->move($fileUri, $folderDestination->getUri());
          $item = $fm->checkItem($folderPath . "/" . $fileName);
          $fm->addItemToJs($item);
          $fm->removePathFromJs($filePath);
        }
        catch (FileException $e) {
          $fm->setMessage($this->t("Couldn't move to folder specified"));
        }
      }
    }
    else {
      $fm->setMessage($this->t("You can\'t drop it into a file"));
    }
  }

}
