<?php

namespace Drupal\Tests\imce\Kernel\Plugin\CKEditorPlugin;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Kernel tests for Imce plugins for CKEditor.
 *
 * @group imce
 */
class ImceTest extends KernelTestBase {

  use StringTranslationTrait;
  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'system',
    'imce',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

  }

}
