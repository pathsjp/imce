<?php

namespace Drupal\Tests\imce\Kernel\Plugin\ImcePlugin;

use Drupal\imce\ImceFM;
use Drupal\imce\Plugin\ImcePlugin\Core;
use Drupal\Tests\imce\Kernel\Plugin\KernelTestBasePlugin;
use Symfony\Component\HttpFoundation\Request;

/**
 * Kernel tests for Imce plugins for Imce Plugin Core.
 *
 * @group imce
 */
class CoreTest extends KernelTestBasePlugin {

  /**
   * The Imce ckeditor plugin.
   *
   * @var \Drupal\imce\Plugin\ImcePlugin\Core
   */
  public $core;

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
    $this->core = new Core([], "text_textarea_with_summary", $this->getPluginDefinations());
    $this->imceFM = new ImceFM($this->getConf(), \Drupal::currentUser(), Request::create("/imce"));
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

  /**
   * The get settings.
   *
   * @return array
   *   Return settings array.
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

  /**
   * Test Core::permissionInfo()
   */
  public function testPermissionInfo() {
    $permissionInfo = $this->core->permissionInfo();
    $this->assertTrue(is_array($permissionInfo));
    $this->assertTrue(in_array('Browse files', $permissionInfo));
    $this->assertTrue(in_array('Browse subfolders', $permissionInfo));
  }

}
