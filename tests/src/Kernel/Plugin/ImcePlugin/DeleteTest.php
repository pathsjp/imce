<?php

namespace Drupal\Tests\imce\Kernel\Plugin\ImcePlugin;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\imce\ImceFM;
use Drupal\imce\Plugin\ImcePlugin\Delete;
use Drupal\imce\Plugin\ImcePlugin\Newfolder;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Kernel tests for Imce plugins for Imce Plugin Core.
 *
 * @group imce
 */
class DeleteTest extends KernelTestBase {

  use StringTranslationTrait;
  use UserCreationTrait;

  /**
   * The Imce ckeditor plugin.
   *
   * @var \Drupal\imce\Plugin\ImcePlugin\Delete
   */
  public $delete;

  /**
   * The Imce file manager.
   *
   * @var \Drupal\imce\ImceFM
   */
  public $imceFM;

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
    $this->delete = new Delete([], "text_textarea_with_summary", $this->getPluginDefinations());
    $this->imceFM = new ImceFM($this->getConf(), \Drupal::currentUser(), Request::create("/imce"));
    $this->imceFM->getPost('newFolder', 'test-1');
    $this->createNewFoder();
  }

  /**
   * This method will be removed.
   */
  public function test() {
    $this->assertEquals('test', 'test');
  }

  public function createNewFoder() {
    $array = [
      'weight' => -99,
      'operations' => [
        'browse' => "opBrowse",
        'uuid' => "opUuid",
      ],
      'id' => "core",
      'label' => "Core",
      'class' => "Drupal\imce\Plugin\ImcePlugin\Core",
      'provider' => "imce",
    ];

    $newFolder = new Newfolder([], 'core', $array);
    $newFolder->opNewfolder($this->imceFM);
  }

  /**
   * Get plugins definations.
   *
   * @return array
   *   Return plugins definations.
   */
  public function getPluginDefinations() {
    return [
      "field_types" => [
        0 => "text_with_summary",
      ],
      "multiple_values" => FALSE,
      "id" => "text_textarea_with_summary",
      "label" => $this->t("Text area with a summary"),
      "class" => TextareaWithSummaryWidget::class,
      "provider" => "text",
    ];
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

  public function testPermissiomInfo() {
    $permissionInfo = $this->delete->permissionInfo();
    $this->assertTrue(is_array($permissionInfo));
    $this->assertTrue(in_array('Delete files', $permissionInfo));
    $this->assertTrue(in_array('Delete subfolders', $permissionInfo));
  }

}
