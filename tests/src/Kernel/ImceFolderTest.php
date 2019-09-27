<?php

namespace Drupal\Tests\imce\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\imce\ImceFolder;

/**
 * Kernel tests for ImceFolder.
 *
 * @group imce
 */
class ImceFolderTest extends KernelTestBase {

  use StringTranslationTrait;

  /**
   * The form delete profile.
   *
   * @var \Drupal\imce\ImceFolder
   */
  protected $imceFolder;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'imce',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['imce']);
    $this->installEntitySchema('imce_profile');
    $this->imceFolder = new ImceFolder('Folder');
  }

  public function testName() {
    $this->assertTrue(is_string($this->imceFolder->name));
    $this->assertEqual($this->imceFolder->name, 'Folder');
  }

}
