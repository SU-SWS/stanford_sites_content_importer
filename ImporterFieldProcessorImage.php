<?php
/**
 * @file
 */

/**
 *
 */
class ImporterFieldProcessorImage extends ImporterFieldProcessorFile {

  /**
   * Wrapper process for the parent function.
   * @param  [type] $entity      [description]
   * @param  [type] $entity_type [description]
   * @param  [type] $field_name  [description]
   * @return [type]              [description]
   */
  public function process(&$entity, $entity_type, $field_name) {
    parent::process($entity, $entity_type, $field_name);
  }

}
