<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Checks that lists filtering on a review state point at a state that exists.
 *
 * Regression test for docs/defects/001-moderated-content-filter.md: deleting
 * a workflow left a list filtering on states that no longer existed, so
 * guides in review were silently missing from it.
 */
class ModerationStateConfigTest extends TestCase {

  /**
   * Folder holding the exported site settings.
   */
  protected const CONFIG_DIR = __DIR__ . '/../../config/sync';

  /**
   * Every "workflow-state" used by a list filter must exist in a workflow.
   */
  public function testViewFiltersUseExistingModerationStates(): void {
    $existing = $this->existingStates();
    $this->assertNotEmpty($existing, 'At least one workflow is exported.');

    $checked = 0;
    foreach (glob(self::CONFIG_DIR . '/views.view.*.yml') as $file) {
      foreach ($this->filterStates(Yaml::parseFile($file)) as $state) {
        $checked++;
        $this->assertContains(
          $state,
          $existing,
          basename($file) . " filters on \"$state\", which is not a state of any exported workflow.",
        );
      }
    }
    $this->assertGreaterThan(0, $checked, 'At least one list filters on a review state.');
  }

  /**
   * Lists every state as "workflow-state", the way list filters name them.
   */
  protected function existingStates(): array {
    $states = [];
    foreach (glob(self::CONFIG_DIR . '/workflows.workflow.*.yml') as $file) {
      $workflow = Yaml::parseFile($file);
      foreach (array_keys($workflow['type_settings']['states'] ?? []) as $state) {
        $states[] = $workflow['id'] . '-' . $state;
      }
    }
    return $states;
  }

  /**
   * Finds the states used by review-state filters anywhere in a list.
   */
  protected function filterStates(array $config): array {
    $found = [];
    $walk = function (array $item) use (&$walk, &$found) {
      if (($item['plugin_id'] ?? '') === 'moderation_state_filter' && is_array($item['value'] ?? NULL)) {
        $found = array_merge($found, array_keys($item['value']));
      }
      foreach ($item as $child) {
        if (is_array($child)) {
          $walk($child);
        }
      }
    };
    $walk($config);
    return $found;
  }

}
