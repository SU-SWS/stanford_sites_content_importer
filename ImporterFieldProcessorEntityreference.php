<?php
/**
 * @file
 */

/**
 *
 */
class ImporterFieldProcessorEntityreference extends ImporterFieldProcessor {

  /**
   * [process description]
   * @param  [type] $entity      [description]
   * @param  [type] $entity_type [description]
   * @param  [type] $field_name  [description]
   * @return [type]              [description]
   */
  public function process(&$entity, $entity_type, $field_name) {
    $values = $entity->{$field_name}[LANGUAGE_NONE];

    foreach ($values as $index => $info) {
      // UUID module on the server overtakes this and serves up UUIDs. This is
      // great except when a site does not have the UUID module available.
      if (!is_int($info['target_id'])) {
        unset($entity->{$field_name}[LANGUAGE_NONE][$index]);
      }
    }

    sort($entity->{$field_name}[LANGUAGE_NONE]);

  }

}
