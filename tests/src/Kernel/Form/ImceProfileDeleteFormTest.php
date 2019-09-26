<?php

namespace Drupal\Tests\imce\Kernel\Form;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\imce\Form\ImceProfileDeleteForm;

/**
 * Kernel tests for ImceProfileDeleteForm.
 *
 * @group imce
 */
class ImceProfileDeleteFormTest extends KernelTestBase {

  use StringTranslationTrait;

  /**
   * The form delete profile.
   *
   * @var object
   */
  protected $profileDeleteForm;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'imce',
  ];

  protected function setUp() {
    parent::setUp();
    $this->profileDeleteForm = new ImceProfileDeleteForm();
  }

  public function testCancelUrl() {
    $url = $this->profileDeleteForm->getCancelUrl();
    $this->assertInstanceOf(Url::class, $url);
    $this->assertTrue(is_string($url->toString()));
    $this->assertSame('/admin/config/media/imce', $url->toString());
    $this->assertEquals('/admin/config/media/imce', $url->toString());
  }

}
