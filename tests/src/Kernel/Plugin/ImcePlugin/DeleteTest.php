<?php

namespace Drupal\Tests\imce\Kernel\Plugin\ImcePlugin;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\imce\ImceFM;
use Drupal\imce\Plugin\ImcePlugin\Delete;
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
    // $this->imceFM = new ImceFM($this->getConf(), \Drupal::currentUser(), Request::create("/imce"));
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

}
