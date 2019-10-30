<?php

namespace Drupal\Tests\imce\Kernel\Plugin\ImcePlugin;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\editor\Entity\Editor;
use Drupal\imce\Plugin\ImcePlugin\Core;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Kernel tests for Imce plugins for Imce Plugin Core.
 *
 * @group imce
 */
class CoreTest extends KernelTestBase {

  use StringTranslationTrait;
  use UserCreationTrait;

  /**
   * The Imce ckeditor plugin.
   *
   * @var \Drupal\imce\Plugin\ImcePlugin\Core
   */
  public $core;

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
  }

  /**
   * This method will be removed.
   */
  public function test() {
    $this->assertEquals('test', 'test');
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

  public function testPermissiomInfo() {
    $permissionInfo = $this->core->permissionInfo();
    $this->assertTrue(is_array($permissionInfo));
    $this->assertTrue(in_array('Browse files', $permissionInfo));
    $this->assertTrue(in_array('Browse subfolders', $permissionInfo));
  }

}
