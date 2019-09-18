<?php

namespace Drupal\Tests\imce\Kernel\Form;

use Drupal\imce\Form\ImceSettingsForm;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Kernel tests for ImceSettingsForm.
 *
 * @group imce
 */
class ImceSettingsFormTest extends KernelTestBase {

  use StringTranslationTrait;

  protected $imceSettingsForm;

  public static $modules = [
    'system',
    'imce',
  ];

  protected function setUp() {
    parent::setUp();
    $this->imceSettingsForm = new ImceSettingsForm(
      $this->container->get('config.factory'),
      $this->container->get('entity_type.manager'),
      $this->container->get('stream_wrapper_manager')
    );
  }

  public function testFormId() {
    $this->assertTrue(is_string($this->imceSettingsForm->getFormId()));
    $this->assertEquals('imce_settings_form', $this->imceSettingsForm->getFormId());
  }

  public function testProfileOptions() {
    $options = $this->imceSettingsForm->getProfileOptions();
    $this->assertTrue(is_array($options));
    $this->assertArraySubset($options, ['' => '-' . $this->t('None') . '-']);
  }

}
