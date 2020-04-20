<?php

namespace Drupal\imce_modal\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "IMCE Modal" plugin.
 *
 * @CKEditorPlugin(
 *   id = "imce_modal",
 *   label = @Translation("IMCE Modal")
 * )
 */
class ImceModal extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  // /**
  //  * {@inheritdoc}
  //  */
  // public function getFile() {
  //   if ($module_path = drupal_get_path('module', 'imce_modal')) {
  //     return $module_path . '/js/plugin.js';
  //   }
  // }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'core/drupal.ajax',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'bgimage' => [
        'label' => $this->t('IMCE Modal File Browser'),
        'image' => drupal_get_path('module', 'imce_modal') . '/js/plugins/ckeditor/icons/image-file-16.png',
      ],
    ];
  }

}
