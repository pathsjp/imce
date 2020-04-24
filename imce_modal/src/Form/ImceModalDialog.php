<?php

namespace Drupal\imce_modal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ImceModalDialog.
 */
class ImceModalDialog extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'imce_modal_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'imce_modal/imce_modal';
    $form['iframeModal'] = [
      '#type' => 'inline_template',
      '#template' => '<iframe with="100%" src="{{ url }}"></iframe>',
      '#context' => [
        'url' => '/imce?sendto=CKEDITOR.imce.sendto&type=image&ck_id=edit-body-0-value',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      \Drupal::messenger()->addMessage($key . ': ' . ($key === 'text_format'?$value['value']:$value));
    }
  }

}
