<?php

namespace Drush\Commands\Marvin\Tests\Unit;

use Drush\marvin\ComposerInfo;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @coversDefaultClass \Drush\marvin\ComposerInfo<extends>
 */
class ComposerInfoTest extends TestCase {

  /**
   * @var \org\bovigo\vfs\vfsStreamDirectory
   */
  protected $rootDir;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->rootDir = vfsStream::setup('ComposerInfo');
  }

  protected function tearDown() {
    parent::tearDown();

    (new Filesystem())->remove($this->rootDir->getName());
  }

  public function casesGetLockFileName(): array {
    return [
      'empty' => [
        'composer.lock',
        '',
      ],
      'basic' => [
        'composer.lock',
        'composer.json',
      ],
      'advanced' => [
        'a/b/c.lock',
        'a/b/c.json',
      ],
    ];
  }

  /**
   * @dataProvider casesGetLockFileName
   */
  public function testGetLockFileName(string $expected, string $jsonFileName) {
    $ci = ComposerInfo::create($jsonFileName);
    $this->assertEquals($expected, $ci->getLockFileName());
  }

  public function casesGetWorkingDirectory(): array {
    return [
      'empty' => [
        '',
        '',
      ],
      'basic' => [
        '',
        'composer.json',
      ],
      'advanced' => [
        'a/b',
        'a/b/c.json',
      ],
    ];
  }

  /**
   * @dataProvider casesGetWorkingDirectory
   */
  public function testGetWorkingDirectory(string $expected, string $jsonFileName) {
    $ci = ComposerInfo::create($jsonFileName);
    $this->assertEquals($expected, $ci->getWorkingDirectory());
  }

  public function casesCreate(): array {
    return [
      'basic' => [
        [
          'json' => [
            'name' => 'aa/bb',
            'type' => 'library',
            'config' => [
              'bin-dir' => 'vendor/bin',
              'vendor-dir' => 'vendor',
            ],

          ],
          'lock' => [
            'packages' => [
              'a/b' => [
                'name' => 'a/b',
              ],
              'c/d' => [
                'name' => 'c/d',
              ],
            ],
            'packages-dev' => [
              'e/f' => [
                'name' => 'e/f',
              ],
              'g/h' => [
                'name' => 'g/h',
              ],
            ],
          ],
        ],
        [
          'name' => 'aa/bb',
        ],
        [
          'packages' => [
            [
              'name' => 'a/b',
            ],
            [
              'name' => 'c/d',
            ],
          ],
          'packages-dev' => [
            [
              'name' => 'e/f',
            ],
            [
              'name' => 'g/h',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesCreate
   */
  public function testCreate(array $expected, array $json, array $lock): void {
    $jsonFileName = $this->rootDir->url() . '/composer.json';
    file_put_contents($jsonFileName, json_encode($json));

    $lockFileName = $this->rootDir->url() . '/composer.lock';
    file_put_contents($lockFileName, json_encode($lock));

    $ci = ComposerInfo::create($jsonFileName);
    $this->assertEquals($expected['json'], $ci->getJson());
    $this->assertEquals($expected['lock'], $ci->getLock());
  }

}
