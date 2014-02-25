<?php
/**
 * @file
 * Sites Content Importer
 * Saves content from a Drupal services REST server.
 *
 * @author Shea McKinney <sheamck@stanford.edu>
 */

require_once "ImporterFieldProcessor.php";
require_once "ImporterFieldProcessorDatetime.php";
require_once "ImporterFieldProcessorEmail.php";
require_once "ImporterFieldProcessorFieldCollection.php";
require_once "ImporterFieldProcessorFile.php";
require_once "ImporterFieldProcessorImage.php";
require_once "ImporterFieldProcessorInterface.php";
require_once "ImporterFieldProcessorLinkField.php";
require_once "ImporterFieldProcessorListText.php";
require_once "ImporterFieldProcessorNumberInteger.php";
require_once "ImporterFieldProcessorTaxonomyTermReference.php";
require_once "ImporterFieldProcessorText.php";
require_once "ImporterFieldProcessorTextLong.php";
require_once "ImporterFieldProcessorTextWithSummary.php";


/**
 * Content Importer class
 */
class SitesContentImporter {

  protected $endpoint;
  protected $resource;
  protected $arguments;
  protected $method;
  protected $process_storage = array();
  protected $bean_uuids = array();
  protected $content_types = array();
  protected $restrictions = array();

  /**
   * [__construct description]
   * @return {[type]} [description]
   */
  public function __construct() {
    // Nothing to see here.
  }

  /**
   * [set_endpoint description]
   * @param [type] $endpoint [description]
   */
  public function set_endpoint($endpoint) {
    $this->endpoint = $endpoint;
  }

  /**
   * [get_endpoint description]
   * @return [type] [description]
   */
  public function get_endpoint() {
    return $this->endpoint;
  }

  /**
   * [set_storage description]
   * @param [type] $key   [description]
   * @param [type] $value [description]
   */
  protected function set_storage($key, $value) {
    $this->process_storage[$key] = $value;
  }

  /**
   * [get_storage description]
   * @param  [type] $key [description]
   * @return [type]      [description]
   */
  public function get_storage($key) {

    if ($key == "all") {
      return $this->process_storage;
    }

    if (isset($this->process_storage[$key])) {
      return $this->process_storage[$key];
    }

    return array();
  }

  /**
   * [add_import_content_type description]
   * @param string $type [description]
   */
  public function add_import_content_type($type = '') {

    if (is_array($type)) {
      $type = array_flip($type);
      $this->content_types = array_merge($this->content_types, $type);
    }
    else {
      $this->content_type[$type] = $type;
    }

  }

  /**
   * [remove_import_content_type description]
   * @param  string $type [description]
   * @return [type]       [description]
   */
  public function remove_import_content_type($type = '') {
    unset($this->content_types[$type]);
  }

  /**
   * [get_import_content_types description]
   * @return [type] [description]
   */
  public function get_import_content_types() {
    return $this->content_types;
  }

  /**
   * [add_uuid_restrictions description]
   * @param array $uuids [description]
   */
  public function add_uuid_restrictions($uuids = array()) {
    $restrictions = $this->get_uuid_restrictions();

    if (!is_array($uuids)) {
      return FALSE;
    }

    $restrictions = array_merge($restrictions, $uuids);
    $this->set_uuid_restrictions($restrictions);
    return $restrictions;
  }

  /**
   * [get_uuid_restrictions description]
   * @return [type] [description]
   */
  public function get_uuid_restrictions() {
    return $this->restrictions;
  }

  /**
   * [set_uuid_restrictions description]
   * @param [type] $uuids [description]
   */
  protected function set_uuid_restrictions($uuids) {
    $this->restrictions = $uuids;
  }

  /**
   * Returns a boolean value on wether a UUID is in the restricted list.
   * @param  [type]  $uuid [description]
   * @return boolean       [description]
   */
  protected function is_restricted_uuid($uuid) {
    $restrictions = $this->get_uuid_restrictions();
    return in_array($uuid, $restrictions);
  }


  /**
   * [process_fields description]
   * @param  [type] $entity      [description]
   * @param  [type] $entity_type [description]
   * @return [type]              [description]
   */
  public function process_fields(&$entity, $entity_type) {
    $fields = field_info_field_map();
    foreach ($fields as $field_name => $field_info) {
      if (isset($entity->{$field_name})) {
        $class_name = "ImporterFieldProcessor";
        $type_name = explode("_", $field_info['type']);
        foreach ($type_name as $index => $name) {
          $class_name .= ucfirst(strtolower($name));
        }
        if (class_exists($class_name)) {
          try {
            $field_processor = new $class_name();
            $field_processor->set_endpoint($this->get_endpoint());
            $field_processor->process($entity, $entity_type, $field_name);
          }
          catch(Exception $e) {
            // No worries. Just continue.
            continue;
          }
        }
        else {
          $no_importer_class = "This is only here for my debugger to pick up :)";
        }
      }
    }
  }

  /**
   * Saves field collection entities.
   * This function should be called just before the host entity is saved and
   * no sooner or there will be errors on the host entity as it gets saved
   * with the field collection entity.
   */
  protected function save_field_collections() {
    $field_collections = &drupal_static('ImporterFieldProcessorFieldCollection', array());
    $uuid_map = array();
    foreach ($field_collections as $k => $fc) {
      $fc->save();
      $uuid_map[$fc->uuid] = $fc;
      unset($field_collections[$k]);
    }
    return $uuid_map;
  }

  /**
   * [set_bean_uuids description]
   * @param [type] $uuids [description]
   */
  public function set_bean_uuids($uuids) {
    $this->bean_uuids = $uuids;
  }

  /**
   * [get_bean_uuids description]
   * @return [type] [description]
   */
  public function get_bean_uuids() {
    return $this->bean_uuids;
  }

  /**
   * Imports and processess beans.
   * @return array an array of imported beans.
   */
  public function import_content_beans() {

    // Endpoint will almost always be the hardcoded one.
    $endpoint = $this->get_endpoint();
    $bean_uuids = $this->get_bean_uuids();

    // Get and save beans.
    $beans = array();
    $bean_types = array();
    $module_implements = module_implements('bean_types');
    foreach ($module_implements as $module) {
      $bean_types += module_invoke($module, 'bean_types');
    }

    foreach ($bean_uuids as $uuid) {
      $bean_result = drupal_http_request($endpoint . "/bean/" . $uuid);
      $bean = ($bean_result->code == "200") ? drupal_json_decode($bean_result->data) : FALSE;

      if (!$bean) {
        watchdog('SitesContentImporter', 'Could not fetch BEAN: ' . $uuid, array(), WATCHDOG_NOTICE);
        if (function_exists('drush_log')) {
          drush_log('Could not fetch BEAN: ' . $uuid, 'error');
        }
        continue;
      }

      // Ensure we don't already have this bean.
      $old_bean = bean_load_delta($bean['delta']);
      if ($old_bean) {
        $old_bean->delete();
      }

      // Add the plugin and remove the bid.
      $plugin_settings = array();
      $bean['plugin'] = new $bean_types[$bean['type']]['handler']['class']($plugin_settings);
      unset($bean['bid']);
      unset($bean['vid']);

      // Instantiate the bean and process all of the fields.
      $bean = new Bean($bean);
      $this->process_fields($bean, 'bean');

      // Field Collections are awful things and can only be saved right before the entity that they are attached to is
      // saved. We stored them for later saving and must do that now.
      $this->save_field_collections();

      // Now we can save Mr. Bean.
      $bean->save();
      $beans[] = $bean;
    }

    return $beans;
  }

  /**
   * [import_vocabulary_trees description]
   * @return [type] [description]
   */
  public function import_vocabulary_trees() {
    $endpoint = $this->get_endpoint();

    // Vocabularies.
    try {
      $vocabularies_result = drupal_http_request($endpoint . "/taxonomy_vocabulary");
    }
    catch(Exception $e) {
      watchdog('SitesContentImporter', 'Could Not Fetch Vocabularies', array(), WATCHDOG_ERROR);
      if (function_exists('drush_log')) {
        drush_log('Could Not Fetch Vocabularies', 'error');
      }
      return FALSE;
    }

    $vocabularies = drupal_json_decode($vocabularies_result->data);

    if (empty($vocabularies)) {
      watchdog('SitesContentImporter', 'Vocabularies Data Was Empty. Did not import.', array(), WATCHDOG_ERROR);
      if (function_exists('drush_log')) {
        drush_log('Vocabularies Data Was Empty. Did not import', 'error');
      }
      return FALSE;
    }

    // Loop through each vocab and do two things.
    // One save the vocab.
    // Two get the terms.
    foreach ($vocabularies as $index => $vocab) {

      // We already have tags!
      $vocab = (object) $vocab;
      if ($vocab->machine_name == "tags") {
        continue;
      }

      // Get terms.
      $data = "vid=" . $vocab->vid;
      // Fetch tree.
      try {
        $tree_result = drupal_http_request($endpoint . "/taxonomy_vocabulary/getTree.json", array('method' => "POST", "data" => $data));
        $tree = ($tree_result->code == "200") ? drupal_json_decode($tree_result->data) : FALSE;
      }
      catch (Exception $e) {
        watchdog('SitesContentImporter', 'Tree was either empty or returned error: ' . $vocab->machine_name, array(), WATCHDOG_NOTICE);
        if (function_exists('drush_log')) {
          drush_log('Tree was either empty or returned error: ' . $vocab->machine_name, 'error');
        }
        continue;
      }

      unset($vocab->vid);

      // Save vocab if no already present.
      $load_vocab = taxonomy_vocabulary_machine_name_load($vocab->machine_name);
      $vocab = ($load_vocab) ? $load_vocab : $vocab;

      if (!isset($vocab->vid)) {
        taxonomy_vocabulary_save($vocab);
      }

      // Save the trees! heh heh had to.
      if (!empty($tree)) {
        foreach ($tree as $k => $term) {
          $term = (object) $term;
          $term->vid = $vocab->vid;
          $term->vocabulary_machine_name = $vocab->machine_name;
          unset($term->tid);
          taxonomy_term_save($term);
        }
      }
    }

    return TRUE;
  }

  /**
   * [importer_content_nodes_recent_by_type description]
   * @return [type] [description]
   */
  public function importer_content_nodes_recent_by_type() {
    $endpoint = $this->get_endpoint();
    $types = $this->get_import_content_types();
    $requests = array();
    $ids = array();

    foreach ($types as $machine_name => $value) {
      try {
        // Can't seem to get the pagesize variable to work. So for now we only get 20 of each.
        // Because of this we will want two pages of stanford_page types. The page param works so we can use that to get more.

        if ($machine_name == "stanford_page") {
          $requests[$machine_name] = drupal_http_request($endpoint . "/node.json?page=0&pagesize=20&fields=nid,uuid&parameters[type]=" . $machine_name);
          $requests[$machine_name . "_2"] = drupal_http_request($endpoint . "/node.json?page=1&pagesize=20&fields=nid,uuid&parameters[type]=" . $machine_name);
        }
        else {
          $requests[$machine_name] = drupal_http_request($endpoint . "/node.json?pagesize=50&fields=nid,uuid&parameters[type]=" . $machine_name);
        }

      }
      catch(Exception $e) {
        watchdog('SitesContentImporter', 'Could not fetch content type: ' . $machine_name, array(), WATCHDOG_NOTICE);
        if (function_exists('drush_log')) {
          drush_log('Could not fetch content type: ' . $machine_name, 'error');
        }
      }
    }

    // Store all the ids from all the responses!
    foreach ($requests as $key => $response) {
      if ($response->code == 200) {
        $data = drupal_json_decode($response->data);
        foreach ($data as $k => $id_array) {
          $ids[$id_array['uuid']] = $id_array;
        }
      }
    }

    // Now we get all the node information.
    // @todo: Stop hammering the server with requests and make this better.

    foreach ($ids as $uuid => $other_ids) {

      if ($this->is_restricted_uuid($uuid)) {
        watchdog('SitesContentImporter', 'Did not import restricted UUID: ' . $uuid, array(), WATCHDOG_NOTICE);
        if (function_exists('drush_log')) {
          drush_log('Did not import restricted UUID: ' . $uuid, 'status');
        }
        continue;
      }

      try {
        $node_request = drupal_http_request($endpoint . "/node/" . $uuid . ".json");
      }
      catch(Exception $e) {
        watchdog('SitesContentImporter', 'Could not fetch node information for: ' . $uuid, array(), WATCHDOG_NOTICE);
        if (function_exists('drush_log')) {
          drush_log('Could not fetch node information for: ' . $uuid, 'error');
        }
        continue;
      }

      if ($node_request->code !== "200") {
        watchdog('SitesContentImporter', 'Could not fetch node information for: ' . $uuid, array(), WATCHDOG_NOTICE);
        if (function_exists('drush_log')) {
          drush_log('Could not fetch node information for: ' . $uuid, 'error');
        }
        continue;
      }

      $node = drupal_json_decode($node_request->data);
      unset($node['nid']);
      unset($node['vid']);
      $node = (object) $node;

      // Do a quick check here to ensure that the node type we are going to process is of the types we
      // requested from the server. Somehow types we don't want are sneaking in.
      if (!array_key_exists($node->type, $types)) {
        // Do no process the node as it is not a valid type.
        continue;
      }

      // Process the fields.
      $this->process_fields($node, 'node');

      // Alter node to save the alias.
      $alias = FALSE;
      if (isset($node->url_alias[0])) {
        $alias = $node->url_alias[0]['alias'];
        unset($node->url_alias);
        $node->path['pathauto'] = FALSE;
      }

      // Save the node.
      node_save($node);

      // Save new pathauto string.
      if ($alias) {
        $path = array(
          'source' => 'node/' . $node->nid,
          'alias' => $alias,
        );
        path_save($path);
      }

    }

  }

}
