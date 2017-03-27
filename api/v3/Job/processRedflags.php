<?php

/**
 * Job.ProcessRedflags API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_job_process_redflags_spec(&$spec) {
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
function civicrm_api3_job_process_redflags($params) {
  $courseKey = CRM_Civimoodle_Util::getCustomFieldKey('courses');
  $userKey = CRM_Civimoodle_Util::getCustomFieldKey('user_id');
  $result = civicrm_api3('Event', 'getsingle', array(
    'return' => array($courseKey),
    'id' => $params['event_id'],
  ));
  $courseIDs = CRM_Utils_Array::value($courseKey, $result, array());
  $courseNames = CRM_Civimoodle_Util::getAvailableCourseNames();
  if (count($courseIDs)) {
    $contactIDs = array();
    $result = civicrm_api3('Participant', 'get', array(
      'return' => array("contact_id", "id"),
      'event_id' => $params['event_id'],
    ));
    foreach ($result['values'] as $value) {
      $contactID = $value['contact_id'];
      if (!array_key_exists($contactID, $contactIDs)) {
        $userID = civicrm_api3('Contact', 'getvalue', array(
          'return' => $userKey,
          'id' => $contactID,
        ));

        // if no moodle $userID then skip
        if (!$userID) {
          continue;
        }

        foreach ($courseIDs as $courseID) {
          // fetch grades for given $courseID and $userID
          $criteria = array(
            'course_id' => $courseID,
            'user_id' => $userID,
          );
          list($isError, $response) = CRM_AFABC_Civimoodle_API::singleton($criteria, TRUE)->getGrades();
          $grades = CRM_Utils_Array::value('items', json_decode($response, TRUE));

          //if grades found for $courseID
          if (!$isError && !empty($grades)) {
            foreach ($grades as $grade) {
              // if Red flag grade found
              if ($grade['name'] == 'Red Flag Scale - Offline Activity' && !empty($grade['grades'][0])) {
                $activityParams = array(
                  'activity_type_id' => 'red_flag',
                  'subject' => CRM_Utils_Array::value($courseID, $courseNames),
                  'source_record_id' => $value['participant_id'],
                  'target_contact_id' => $value['contact_id'],
                );
                // create/update/delete red flag activity
                CRM_AFABC_Civimoodle_Util::recordRedFlagActivity($activityParams, $grade['grades'][0]);
              }
            }
          }
        }
      }
      $contactIDs[$contactID] = NULL;
    }
  }
}
