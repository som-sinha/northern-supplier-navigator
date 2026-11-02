<?php

namespace Drupal\nsn_migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Finds the rows in another CSV file that belong to the current row.
 *
 * For guide GDE-001, this returns every row of checklist_items.csv whose
 * guide_id is GDE-001, in sequence order.
 *
 * Configuration:
 * - path: the CSV file to search, relative to the Drupal root (web/).
 * - match_column: the column in that file that holds the parent's ID.
 * - sort_column: (optional) a number column to sort the matches by.
 */
#[MigrateProcess('nsn_csv_children')]
class CsvChildren extends ProcessPluginBase {

  /**
   * Rows already read, keyed by file path, so each file is read only once.
   */
  protected static array $files = [];

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $match = $this->configuration['match_column'];
    $children = array_filter(
      $this->readFile($this->configuration['path']),
      fn (array $child) => $child[$match] === $value,
    );

    if (isset($this->configuration['sort_column'])) {
      $sort = $this->configuration['sort_column'];
      usort($children, fn (array $a, array $b) => (int) $a[$sort] <=> (int) $b[$sort]);
    }

    return array_values($children);
  }

  /**
   * Reads a CSV file into a list of rows keyed by column name.
   */
  protected function readFile(string $path): array {
    if (!isset(static::$files[$path])) {
      $handle = @fopen($path, 'r');
      if ($handle === FALSE) {
        throw new MigrateException("Could not open $path.");
      }
      $header = fgetcsv($handle, escape: '');
      $rows = [];
      while (($line = fgetcsv($handle, escape: '')) !== FALSE) {
        if ($line !== [NULL]) {
          $rows[] = array_combine($header, $line);
        }
      }
      fclose($handle);
      static::$files[$path] = $rows;
    }
    return static::$files[$path];
  }

}
