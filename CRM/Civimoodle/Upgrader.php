<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Civimoodle_Upgrader extends CRM_Civimoodle_Upgrader_Base {

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

    civicrm_api3('Navigation', 'create', array(
      'label' => ts('CiviCRM Moodle Integration', array('domain' => 'biz.jmaconsulting.civimoodle.afabc')),
      'name' => 'moodle_settings',
      'url' => 'civicrm/moodle/setting?reset=1',
      'domain_id' => CRM_Core_Config::domainID(),
      'is_active' => 1,
      'parent_id' => civicrm_api3('Navigation', 'getvalue', array(
        'return' => "id",
        'name' => "System Settings",
      )),
      'permission' => 'administer CiviCRM',
    ));

    // Create custom set 'Moodle Credentials'
    $customGroup = civicrm_api3('custom_group', 'create', array(
      'title' => ts('Moodle Credentials', array('domain' => 'biz.jmaconsulting.civimoodle.afabc')),
      'name' => 'moodle_credential',
      'extends' => 'Individual',
      'domain_id' => CRM_Core_Config::domainID(),
      'style' => 'Tab',
      'is_active' => 1,
      'collapse_adv_display' => 0,
      'collapse_display' => 0
    ));
    foreach (CRM_Civimoodle_FieldInfo::getAttributes('moodle_credential') as $param) {
      civicrm_api3('custom_field', 'create', array_merge($param, array(
        'custom_group_id' => $customGroup['id'],
        'is_searchable' => 1,
      )));
    }

    // Create option group 'Available Courses'
    $optionGroup = civicrm_api3('OptionGroup', 'create', array(
      'title' => 'Available courses',
      'name' => 'available_courses',
      'is_active' => 1,
      'is_reserved' => 1,
    ));
    civicrm_api3('OptionValue', 'create', array(
      'option_group_id' => $optionGroup['id'],
      'label' => 'Dummy',
      'value' => 'dummy',
    ));

    // Create custom set 'Available Courses'
    $customGroup = civicrm_api3('custom_group', 'create', array(
      'title' => ts('Available Courses', array('domain' => 'biz.jmaconsulting.civimoodle.afabc')),
      'name' => 'moodle_courses',
      'extends' => 'Event',
      'domain_id' => CRM_Core_Config::domainID(),
      'is_active' => 1,
      'collapse_adv_display' => 0,
      'collapse_display' => 0
    ));
    foreach (CRM_Civimoodle_FieldInfo::getAttributes('moodle_courses') as $param) {
      civicrm_api3('custom_field', 'create', array_merge($param, array(
        'custom_group_id' => $customGroup['id'],
        'option_group_id' => $optionGroup['id'],
        'is_searchable' => 1,
      )));
    }

    CRM_Core_BAO_Navigation::resetNavigation();
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   *
   */
  public function uninstall() {
    self::changeNavigation('delete');

    $result = civicrm_api3('Activity', 'get', array('activity_type_id' => 'red_flag'));
    // delete all desired activities
    foreach ($result['values'] as $id => $doNotCare) {
      civicrm_api3('Activity', 'delete', array('id' => $id));
    }
    // at last delete the activity type
    civicrm_api3('OptionValue', 'get', array(
      'name' => 'red_flag',
      'api.OptionValue.delete' => array(
        'id' => "\$value.id",
      ),
    ));

    $customGroupID = civicrm_api3('custom_group', 'getvalue', array(
     'name' => 'moodle_courses',
     'return' => 'id',
    ));
    if (!empty($customGroupID)) {
      foreach (CRM_Civimoodle_FieldInfo::getAttributes('moodle_courses') as $param) {
        $customFieldID = civicrm_api3('custom_field', 'getvalue', array(
          'custom_group_id' => $customGroupID,
          'name' => $param['name'],
          'return' => 'id',
        ));
        if (!empty($customFieldID)) {
          civicrm_api3('custom_field', 'delete', array('id' => $customFieldID));
        }
      }
      civicrm_api3('custom_group', 'delete', array('id' => $customGroupID));
    }

    $customGroupID = civicrm_api3('custom_group', 'getvalue', array(
      'name' => 'moodle_credential',
      'return' => 'id',
    ));
    if (!empty($customGroupID)) {
      foreach (CRM_Civimoodle_FieldInfo::getAttributes('moodle_credential') as $param) {
        $customFieldID = civicrm_api3('custom_field', 'getvalue', array(
          'custom_group_id' => $customGroupID,
          'name' => $param['name'],
          'return' => 'id',
        ));
        if (!empty($customFieldID)) {
          civicrm_api3('custom_field', 'delete', array('id' => $customFieldID));
        }
      }
      civicrm_api3('custom_group', 'delete', array('id' => $customGroupID));
    }

    Civi::settings()->revert('moodle_access_token');
    Civi::settings()->revert('moodle_domain');
  }

  /**
   * Example: Run a simple query when a module is enabled.
   *
   */
  public function enable() {
    self::changeNavigation('enable');
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
    self::changeNavigation('disable');
    civicrm_api3('OptionValue', 'get', array(
      'name' => "red_flag",
      'api.OptionValue.create' => array(
        'id' => "\$value.id",
        'is_active' => FALSE,
      ),
    ));
  }

  /**
   * disable/enable/delete Moodle Setting link
   *
   * @param string $action
   * @throws \CiviCRM_API3_Exception
   */
  public static function changeNavigation($action) {
    $names = array('moodle_settings');
    foreach ($names as $name) {
      if ($name == 'delete') {
        $id = civicrm_api3('Navigation', 'getvalue', array(
          'return' => "id",
          'name' => $name,
        ));
        if ($id) {
          civicrm_api3('Navigation', 'delete', array('id' => $id));
        }
      }
      else {
        $isActive = ($action == 'enable') ? 1 : 0;
        CRM_Core_BAO_Navigation::setIsActive(
          CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', $name, 'id', 'name'),
          $isActive
        );
      }
    }

    CRM_Core_BAO_Navigation::resetNavigation();
  }

}
