<?php

/**
 * Class to send Moodle API request
 */
class CRM_AFABC_Civimoodle_API {

  /**
   * Instance of this object.
   *
   * @var CRM_CiviMoodle_API
   */
  public static $_singleton = NULL;

  /**
   * Search parameters later formated into API url arguments
   *
   * @var array
   */
  protected $_searchParams;

  /**
   * Instance of CRM_Utils_HttpClient
   *
   * @var CRM_Utils_HttpClient
   */
  protected $_httpClient;

  /**
   * Variable to store Moodle web access token
   *
   * @var string
   */
  protected $_wsToken;

  /**
   * Variable to store Moodle web domain
   *
   * @var string
   */
  protected $_domain;

  /**
   * The constructor sets search parameters and instantiate CRM_Utils_HttpClient
   */
  public function __construct($searchParams = array()) {
    $this->_searchParams = $searchParams;
    $this->_httpClient = new CRM_Utils_HttpClient();
    $this->_wsToken = Civi::settings()->get('moodle_access_token');
    $this->_domain = Civi::settings()->get('moodle_domain');
  }

  /**
   * Singleton function used to manage this object.
   *
   * @param array $searchParams
   *   Moodle parameters
   *
   * @return CRM_CiviMoodle_API
   */
  public static function &singleton($searchParams = array(), $reset = FALSE) {
    if (self::$_singleton === NULL || $reset) {
      self::$_singleton = new CRM_AFABC_Civimoodle_API($searchParams);
    }
    return self::$_singleton;
  }

  /**
   * Function to core_grades_get_grades webservice to enroll moodle user for given course
   */
  public function getGrades() {
    return $this->sendRequest('core_grades_get_grades');
  }

  /**
   * Function used to make Moodle API request
   *
   * @param string $apiFunc
   *   Donor Search API function name
   *
   * @return array
   */
  public function sendRequest($apiFunc) {
    $searchArgs = array(
      'wstoken=' . $this->_wsToken,
      'wsfunction=' . $apiFunc,
      'moodlewsrestformat=json',
    );

    switch ($apiFunc) {
      case 'core_grades_get_grades':
          $searchArgs[] = "courseid=" . $this->_searchParams['course_id'];
          $searchArgs[] = "userids[0]=" . $this->_searchParams['user_id'];
        break;

      default:
        //do nothing
        break;
    }

    // send API request with desired search arguments
    $url = sprintf("%swebservice/rest/server.php?%s",
      CRM_Utils_File::addTrailingSlash($this->_domain, '/'),
      str_replace(' ', '+', implode('&', $searchArgs))
    );
    list($status, $response) = $this->_httpClient->get($url);

    return array(
      self::recordError($response),
      $response,
    );
  }

  /**
   * Record error response if there's anything wrong in $response
   *
   * @param string $response
   *   fetched data from Moodle API
   *
   * @return bool
   *   Found error ? TRUE or FALSE
   */
  public static function recordError($response) {
    $isError = FALSE;
    $response = json_decode($response, TRUE);

    if (!empty($response['exception'])) {
      civicrm_api3('SystemLog', 'create', array(
        'level' => 'error',
        'message' => $response['message'],
        'contact_id' => CRM_Core_Session::getLoggedInContactID(),
      ));
      $isError = TRUE;
    }

    return $isError;
  }

}
