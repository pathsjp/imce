<?php

namespace Drupal\Tests\imce\Kernel\Plugin\ImcePlugin;

use Drupal\imce\ImceFM;
use Drupal\imce\Plugin\ImcePlugin\Newfolder;
use Drupal\Tests\imce\Kernel\Plugin\KernelTestBasePlugin;
use Symfony\Component\HttpFoundation\Request;

/**
 * Kernel tests for Imce plugins for Imce Plugin NewFolder.
 *
 * @group imce
 */
class NewFolderTest extends KernelTestBasePlugin {

  /**
   * The Imce ckeditor plugin.
   *
   * @var \Drupal\imce\Plugin\ImcePlugin\Newfolder
   */
  public $newFolder;

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
    $this->installSchema('system', ['sequences']);
    $this->installConfig('imce');
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->newFolder = new Newfolder([], 'newfolder', $this->getPluginDefinations());
  }

  public function test() {
    $this->assertEquals('test', 'test');
  }

  public function getConf() {
    return [
      'permissions' => ['all' => TRUE],
    ];
  }

  public function setActiveFolder() {
    $this->imceFM->activeFolder = new ImceFolder('.', $this->getConf());
    $this->imceFM->activeFolder->setPath('.');
    $this->imceFM->activeFolder->setFm($this->imceFM);
  }

  public function setParametersRequest() {
    $this->imceFM->request->request->add([
      'jsop' => 'newfolder',
      'token' => 'LLuA1R0aUOzoduSJkJxN5aoHVdJnQk8LbTBgdivOU4Y',
      'active_path' => '.',
      'newfolder' => 'folder-test',
    ]);
  }

  public function getPluginDefinations() {
    return [
      'weight' => '-15',
      'operations' => [
        'newfolder' => "opNewfolder",
      ],
      'id' => "newfolder",
      'label' => "New Folder",
      'class' => "Drupal\imce\Plugin\ImcePlugin\Newfolder",
      'provider' => "imce",
    ];
  }

}
