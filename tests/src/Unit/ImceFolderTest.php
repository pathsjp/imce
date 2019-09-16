<?php

namespace Drupal\Tests\imce\Unit;

use Drupal\imce\ImceFolder;
use Drupal\Tests\UnitTestCase;

/**
 * Test ImceFile.
 *
 * @group imce
 */
class ImceFolderTest extends UnitTestCase {

  protected $imceFolder;

  public function setUp() {
    parent::setUp();
    $this->imceFolder = $this->createMock(ImceFolder::class);
  }

  public function testType() {
    $this->assertNotEmpty($this->imceFolder->type);
    $this->assertTrue(is_string($this->imceFolder->type));
    $this->assertEquals('folder', $this->imceFolder->type);
  }

}
