<?php

declare(strict_types=1);

namespace Drupal\Tests\nsn_migrate\Unit;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\nsn_migrate\Plugin\migrate\process\CsvChildren;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the process plugin that finds a guide's rows in a child CSV file.
 */
#[CoversClass(CsvChildren::class)]
#[Group('nsn_migrate')]
class CsvChildrenTest extends UnitTestCase {

  /**
   * Path of the temporary CSV file used by a test.
   */
  protected string $csvPath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Items are deliberately out of order, with a two-digit sequence, a
    // quoted comma and a trailing blank line, like a real export.
    $this->csvPath = tempnam(sys_get_temp_dir(), 'nsn_csv_');
    file_put_contents($this->csvPath, implode("\n", [
      'item_id,guide_id,sequence,action',
      'CHK-A-10,GDE-A,10,Tenth item',
      'CHK-B-01,GDE-B,1,Other guide',
      'CHK-A-02,GDE-A,2,"Second item, with a comma"',
      'CHK-A-01,GDE-A,1,First item',
      '',
    ]));
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    unlink($this->csvPath);
    parent::tearDown();
  }

  /**
   * Runs the plugin for one guide ID and returns the matching item IDs.
   */
  protected function childIds(string $guide_id, array $configuration): array {
    $plugin = new CsvChildren($configuration, 'nsn_csv_children', []);
    $rows = $plugin->transform(
      $guide_id,
      $this->createMock(MigrateExecutableInterface::class),
      new Row(),
      'field_checklist',
    );
    return array_column($rows, 'item_id');
  }

  /**
   * Only the rows that belong to the guide come back.
   */
  public function testReturnsOnlyTheGuidesRows(): void {
    $ids = $this->childIds('GDE-B', ['path' => $this->csvPath, 'match_column' => 'guide_id']);
    $this->assertSame(['CHK-B-01'], $ids);
  }

  /**
   * Rows are sorted as numbers, so 10 comes after 2, not before it.
   */
  public function testSortsBySequenceAsNumbers(): void {
    $ids = $this->childIds('GDE-A', [
      'path' => $this->csvPath,
      'match_column' => 'guide_id',
      'sort_column' => 'sequence',
    ]);
    $this->assertSame(['CHK-A-01', 'CHK-A-02', 'CHK-A-10'], $ids);
  }

  /**
   * Without a sort column, rows keep the order they have in the file.
   */
  public function testKeepsFileOrderWithoutSortColumn(): void {
    $ids = $this->childIds('GDE-A', ['path' => $this->csvPath, 'match_column' => 'guide_id']);
    $this->assertSame(['CHK-A-10', 'CHK-A-02', 'CHK-A-01'], $ids);
  }

  /**
   * A guide with no child rows gets an empty list, not an error.
   */
  public function testGuideWithNoRowsGetsEmptyList(): void {
    $ids = $this->childIds('GDE-NONE', ['path' => $this->csvPath, 'match_column' => 'guide_id']);
    $this->assertSame([], $ids);
  }

  /**
   * A missing file stops the import with a clear message.
   */
  public function testMissingFileThrows(): void {
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('Could not open');
    $this->childIds('GDE-A', ['path' => '/no/such/file.csv', 'match_column' => 'guide_id']);
  }

}
