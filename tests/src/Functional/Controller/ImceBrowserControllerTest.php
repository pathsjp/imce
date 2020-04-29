<?php

namespace Drupal\Tests\imce\Functional\Controller;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides automated tests for the imce module.
 *
 * @group imce
 */
class ImceBrowserControllerTest extends BrowserTestBase {

  /**
   * {@inheritDoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['imce'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer imce']);
    $this->drupalLogin($this->user);
  }

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "imce ImceBrowserController's controller functionality",
      'description' => 'Test Unit for module imce and controller ImceBrowserController.',
      'group' => 'Other',
    ];
  }

  /**
   * Tests imce functionality.
   */
  public function testImceBrowserController() {
    $this->drupalGet(Url::fromRoute('imce.file_browser'));
    $this->assertSession()->statusCodeEquals(200);
  }

}
