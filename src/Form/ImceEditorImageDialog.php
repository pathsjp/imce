<?php

namespace Drupal\imce\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\editor\EditorInterface;
use Drupal\editor\Form\EditorImageDialog;

/**
 * Provides an image dialog for text editors.
 */
class ImceEditorImageDialog extends EditorImageDialog {

  /**
   * Build ImceEditorImageDialog form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\editor\Entity\Editor $editor
   *   The text editor to which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, Editor $editor = NULL) {
    $form = parent::buildForm($form, $form_state, $editor);

    // Disable image upload field if editor uses ImceImage button.
    if ($this->imceImagePluginAvailable($editor) && isset($form['fid'])) {
      $form['fid']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * Checks does requested editor have ImceImage button.
   *
   * @param \Drupal\editor\EditorInterface|null $editor
   *   Editor to check.
   *
   * @return bool
   *   Does requested editor have ImceImage button.
   */
  protected function imceImagePluginAvailable(EditorInterface $editor = NULL) {

    if ($editor instanceof EditorInterface) {
      $settings = $editor->getSettings();

      if (isset($settings['toolbar'], $settings['toolbar']['rows'])) {
        foreach ($settings['toolbar']['rows'] as $row) {
          foreach ($row as $section) {
            if (!empty($section['items']) && (in_array('ImceImage', $section['items']) || in_array('imceModal', $section['items']))) {

              return TRUE;
            }
          }
        }
      }
    }

    return FALSE;
  }

}
