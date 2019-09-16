<?php

namespace Drupal\Tests\imce\Unit;

use Drupal\imce\ImceFile;
use Drupal\Tests\UnitTestCase;

/**
 * Test ImceFile.
 *
 * @group imce
 */
class ImceFileTest extends UnitTestCase {

  protected $imceFile;

  public function setUp() {
    parent::setUp();
    $this->imceFile = $this->createMock(ImceFile::class);
  }

  public function testType() {
    $this->assertEquals('file', $this->imceFile->type);
  }

}
