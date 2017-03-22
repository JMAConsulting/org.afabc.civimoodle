<?php

class CRM_AFABC_Civimoodle_Util {

  /**
   * Function to fetch course names in array('id' => 'fullname') format
   *
   * @return array $options
   *       Array of available course names
   */
  public static function getAvailableCourseNames() {
    list($isError, $response) = CRM_Civimoodle_API::singleton()->getCourses();
    $courses = json_decode($response, TRUE);
    if (!$isError && isset($courses) && count($courses)) {
      $options = array();
      foreach ($courses as $course) {
        if (!empty($course['categoryid'])) {
          $options[$course['id']] = $course['fullname'];
        }
      }
    }

    return $options;
  }

  /**
   * Function to create/update/delete red flag activity
   *
   * @param array $activityParams
   * @param array $grade
   *
   */
  public static function recordRedFlagActivity($activityParams, $grade) {
    $result = civicrm_api3('Activity', 'get', $activityParams);
    $activityID = CRM_Utils_Array::value('id', $result);

    if ($grade['str_grade'] == 'Many Concerns') {
      if (!empty($activityID)) {
        $activityParams['id'] = $activityID;
        $activityParams['description'] = $grade['str_feedback'];
      }
      civicrm_api3('Activity', 'create', $activityParams);
    }
    elseif ($grade['str_grade'] == 'No Concerns' && $activityID) {
      civicrm_api3('Activity', 'delete', array('id' => $activityID));
    }
  }

}
