<?php

namespace Drupal\imce_modal\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
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

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    if ($module_path = drupal_get_path('module', 'imce_modal')) {
      return $module_path . '/js/plugin.js';
    }
  }

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
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'imceModal' => [
        'label' => $this->t('IMCE Modal File Browser'),
        'image' => drupal_get_path('module', 'imce_modal') . '/js/plugins/ckeditor/icons/image-file-16.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\editor\Form\EditorFileDialog
   * @see ckeditor_bgimage_upload_settings_form()
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $form['iframeModal'] = [
      '#type' => 'inline_template',
      '#template' => '<iframe with="100%" src="{{ url }}"></iframe>',
      '#context' => [
        'url' => '/en/imce?sendto=CKEDITOR.imce.sendto&type=image&ck_id=edit-body-0-value',
      ],
    ];

    return $form;
  }

}