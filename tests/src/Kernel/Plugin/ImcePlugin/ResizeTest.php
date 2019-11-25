<?php

namespace Drupal\Tests\imce\Kernel\Plugin\ImcePlugin;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\imce\ImceFM;
use Drupal\imce\ImceFolder;
use Drupal\imce\Plugin\ImcePlugin\Core;
use Drupal\imce\Plugin\ImcePlugin\Resize;
use Drupal\Tests\imce\Kernel\Plugin\KernelTestBasePlugin;
use Symfony\Component\HttpFoundation\Request;

/**
 * Kernel tests for Imce plugins for Imce Plugin Core.
 *
 * @group imce
 */
class ResizeTest extends KernelTestBasePlugin {

  use StringTranslationTrait;

  /**
   * The Imce ckeditor plugin.
   *
   * @var \Drupal\imce\Plugin\ImcePlugin\Resize
   */
  public $resize;

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
    'config',
    'file',
    'system',
    'imce',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->imceFM = $this->getImceFM();
    $this->resize = new Resize([], 'resize', $this->getPluginDefinations());
    $this->setParametersRequest();
    $this->setActiveFolder();

    $this->resize->opResize($this->imceFM);
  }

  /**
   * This method will be removed.
   */
  public function test() {
    $this->assertEquals('test', 'test');
  }

  /**
   * Get permissions settings.
   *
   * @return array
   *   Return the array with permissions.
   */
  public function getConf() {
    return [
      'permissions' => ['all' => TRUE],
    ];
  }
  /**
   * Set the active folder.
   */
  public function setActiveFolder() {
    $this->imceFM->activeFolder = new ImceFolder('.', $this->getConf());
    $this->imceFM->activeFolder->setPath('.');
    $this->imceFM->activeFolder->setFm($this->imceFM);
  }

  /**
   * Set the request parameters.
   */
  public function setParametersRequest() {
    $this->imceFM->request->request->add([
      'jsop' => 'resize',
      'token' => 'LLuA1R0aUOzoduSJkJxN5aoHVdJnQk8LbTBgdivOU4Y',
      'active_path' => '.',
      'selection' => [
        './image-to-resize.png',
      ],
      'width' => '500',
      'height' => '281',
      'copy' => '1',
    ]);
  }

  /**
   * Get plugins definations to new folder.
   */
  public function getPluginDefinations() {
    return [
      'weight' => 0,
      'operations' => [
        'resize' => 'opResize',
      ],
      'id' => 'resize',
      'label' => 'Resize',
      'class' => 'Drupal\imce\Plugin\ImcePlugin\Resize',
      'provider' => 'imce',
    ];
  }

  /**
   * Test Resize::permissionInfo()
   */
  public function testPermissiomInfo() {
    $permissionInfo = $this->resize->permissionInfo();
    $this->assertTrue(is_array($permissionInfo));
    $this->assertTrue(in_array($this->t('Resize images'), $permissionInfo));
  }

}