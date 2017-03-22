<?php

/**
 * Collection of upgrade steps.
 */
class CRM_AFABC_Civimoodle_Upgrader extends CRM_AFABC_Civimoodle_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   *
   */
  public function install() {
    civicrm_api3('OptionGroup', 'get', array(
      'name' => "activity_type",
      'api.OptionValue.create' => array(
        'option_group_id' => "\$value.id",
        'label' => "Red Flag",
        'name' => "red_flag",
        'description' => 'Activity to record that a student is red flagged for some grade',
        'is_reserved' => TRUE,
      ),
    ));
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   *
   */
  public function uninstall() {
    $result = civicrm_api3('Activity', 'get', array('activity_type_id' => 'red_flag'));
    // delete all desired activities
    foreach ($result['values'] as $id => $doNotCare) {
      civicrm_api3('Activity', 'delete', array('id' => $id));
    }
  }

  /**
   * Example: Run a simple query when a module is enabled.
   *
   */
  public function enable() {
    civicrm_api3('OptionValue', 'get', array(
      'name' => "red_flag",
      'api.OptionValue.create' => array(
        'id' => "\$value.id",
        'is_active' => TRUE,
      ),
    ));
  }

  /**
   * Example: Run a simple query when a module is disabled.
   *
   */
  public function disable() {
    civicrm_api3('OptionValue', 'get', array(
      'name' => "red_flag",
      'api.OptionValue.create' => array(
        'id' => "\$value.id",
        'is_active' => FALSE,
      ),
    ));
  }

}
