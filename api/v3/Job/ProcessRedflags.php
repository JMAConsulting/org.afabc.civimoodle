<?php

/**
 * Job.ProcessRedflags API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_job_ProcessRedflags_spec(&$spec) {
  $spec['event_id']['api.required'] = 1;
}

/**
 * Job.ProcessRedflags API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_job_ProcessRedflags($params) {
  $courseKey = CRM_Civimoodle_Util::getCustomFieldKey('courses');
  $userKey = CRM_Civimoodle_Util::getCustomFieldKey('user_id');
  $result = civicrm_api3('Event', 'getsingle', array(
    'return' => array($courseKey),
    'id' => $params['event_id'],
  ));
  $courseIDs = CRM_Utils_Array::value($courseKey, $result, array());
  if (count($courseIDs)) {
    $contactIDs = array();
    $result = civicrm_api3('Participant', 'get', array(
      'return' => array("contact_id", "id"),
      'event_id' => $params['event_id'],
    ));
    foreach ($result as $value) {
      $contactID = $value['contact_id'];
      if (!array_key_exists($contactID, $contactIDs)) {
        $userID = civicrm_api3('Contact', 'getvalue', array(
          'return' => $userKey,
          'id' => $contactID,
        ));
        if (!$userID) {
          continue;
        }
        // 2.3 call 'core_grades_get_grades' webservice to fetch all grades on basis of course id and user id (fetched in 2.2)
      }
      $contactIDs[$contactID] = NULL;
    }
  }
}
