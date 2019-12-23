<?php

namespace Drupal\Tests\imce\Kernel;

use Drupal\imce\ImceFM;
use Drupal\imce\ImceFolder;
use Drupal\Tests\imce\Kernel\Plugin\KernelTestBasePlugin;

/**
 * Kernel tests for ImceFolder.
 *
 * @group imce
 */
class ImceFolderTest extends KernelTestBasePlugin {

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

  /**
   * Test inherited method ImceFolder::fm().
   */
  public function testFM() {
    $this->assertInstanceOf(ImceFM::class, $this->imceFolder->fm());
  }

  /**
   * Test variable ImceFolder::files after scan.
   */
  public function testFiles() {
    $files = $this->imceFolder->files;
    $this->assertTrue(is_array(($files)));
  }

  /**
   * Test variable ImceFolder::subfolders after scan.
   */
  public function testSubfolders() {
    $subfolders = $this->imceFolder->subfolders;
    $this->assertTrue(is_array(($subfolders)));
  }

  /**
   * Test variable ImceFolder::name after scan.
   */
  public function testName() {
    $this->assertTrue(is_string($this->imceFolder->name));
    $this->assertEqual($this->imceFolder->name, 'js');
  }

  /**
   * Test method ImceFolder::getPath().
   */
  public function testPath() {
    $this->imceFolder->setPath('js');
    $path = $this->imceFolder->getPath();
    $this->assertTrue(is_string($path));
  }

  /**
   * Test variable ImceFolder::items after scan.
   */
  public function testItem() {
    $items = $this->imceFolder->items;
    $this->assertTrue(is_array(($items)));
  }

  /**
   * Test variable ImceFolder::scanned after scan.
   */
  public function testScanned() {
    $this->assertTrue(is_bool($this->imceFolder->scanned));
    $this->assertTrue($this->imceFolder->scanned);
  }

  /**
   * Settings needed to run tests.
   */
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
