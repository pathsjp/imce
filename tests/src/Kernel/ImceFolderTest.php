<?php

namespace Drupal\Tests\imce\Kernel;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\imce\ImceFolder;
use Drupal\Tests\imce\Kernel\Plugin\KernelTestBasePlugin;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Kernel tests for ImceFolder.
 *
 * @group imce
 */
class ImceFolderTest extends KernelTestBasePlugin {

  use StringTranslationTrait;
  use UserCreationTrait;

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
    'user',
    'system',
    'imce',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->imceFolder = new ImceFolder('js', $this->getConf());
    $this->imceFolder->setFm($this->getImceFM());
    $this->imceFolder->scan();
  }

  public function testFiles() {
    $files = $this->imceFolder->files;
    $this->assertTrue(is_array(($files)));
  }

  public function testSubfolders() {
    $subfolders = $this->imceFolder->subfolders;
    $this->assertTrue(is_array(($subfolders)));
  }

  public function testName() {
    $this->assertTrue(is_string($this->imceFolder->name));
    $this->assertEqual($this->imceFolder->name, 'js');
  }

  public function testPath() {
    $this->imceFolder->setPath('js');
    $path = $this->imceFolder->getPath();
    $this->assertTrue(is_string($path));
  }

  public function testItem() {
    $items = $this->imceFolder->items;
    $this->assertTrue(is_array(($items)));
  }

  public function testScanned() {
    $this->assertTrue(is_bool($this->imceFolder->scanned));
    $this->assertTrue($this->imceFolder->scanned);
  }

  public function getConf() {
    return [
      "extensions" => "*",
      "maxsize" => '104857600.0',
      "quota" => 0,
      "maxwidth" => 0,
      "maxheight" => 0,
      "replace" => 0,
      "thumbnail_style" => "",
      "folders" => [
        "." => [
          "permissions" => [
            "all" => TRUE,
          ],
        ],
      ],
      "pid" => "admin",
      "scheme" => "public",
      "root_uri" => "public://",
      "root_url" => "/sites/default/files",
      "token" => "Vof6182Y9jbV1jFfCU0arR2XDI8qs-OfO8c-R-IbkTg",
    ];
  }

}
