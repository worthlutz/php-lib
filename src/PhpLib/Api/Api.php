<?php
namespace PhpLib\Api;

use PhpLib\Api\ApiEndpoint;

class Api {

  /**
   * Property: secretKey
   * A static string which should be set to the same key set in the
   * ApiEndpoint class so encoding and decoding JWT will work.
   * TODO:This would be done for both in the start-api-v2 program.
   */
  static $secretKey = "Api_key";

  /**
   * Property: authHeader
   * The HTTP Auth Header
   */
  protected $authHeader = '';

  /**
   * Property: args
   * Any additional URI components after the endpoint and verb have been removed, in our
   * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
   * or /<endpoint>/<arg0>
   */
  protected $args = Array();

  /**
   * Property: endpoint
   * The Model requested in the URI. eg: /files
   */
  protected $endpoint = '';

  /**
   * Property: method
   * The HTTP method this request was made in, either GET, POST, PUT or DELETE
   */
  protected $method = '';

  /**
   * Property: params
   * The HTTP inputs from GET, POST, PUT or DELETE
   */
  protected $get_vars = array();

  /**
   * Property: params
   * The HTTP inputs from GET, POST, PUT or DELETE
   */
  protected $post_vars = array();

  /**
   * Property: method
   * The HTTP method request content
   */
  protected $requestBody = NULL;

  /**
   * Property: endpointNameSpace
   * Namespace for the endpoints of the API
   */
  protected $endpointNameSpace = '';

  /**
   * Constructor: __construct
   * Allow for CORS, assemble and pre-process the data
   */
  public function __construct($endpointNameSpace) {
    header("Access-Control-Allow-Orgin: *");
    header("Access-Control-Allow-Methods: *");
    header("Content-Type: application/json");

    $this->endpointNameSpace = $endpointNameSpace;

    // authorization header from request
    $this->authHeader = $this->getAuthHeader();

    $this->method = $_SERVER['REQUEST_METHOD'];
    if ($this->method == 'POST' AND array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
      if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
          $this->method = 'DELETE';
      } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
          $this->method = 'PUT';
      } else {
        $errorMsg = "Unexpected HTTP_X_HTTP_METHOD Header- " . $_SERVER['HTTP_X_HTTP_METHOD'];
        throw new \Exception($errorMsg, 400);
      }
    }

    // get api args extracted by .htaccess rewrite
    $apiArgs = $_GET['api_args'];
    $this->args = explode('/', trim($apiArgs, '/'));

    // endpoint is 1st arg
    $this->endpoint = array_shift($this->args);

    // pre-process inputs
    $this->requestBody = file_get_contents('php://input');

    switch($this->method) {
      case 'DELETE':
      case 'PATCH':
      case 'POST':
      case 'PUT':
        if (strlen($this->requestBody) > 0) {
          $this->content = $this->requestBody;
          // TODO: clean this up and fix if decode fails!
          $xx = json_decode($this->content, TRUE);
          $this->post_vars = $this->_cleanInputs($xx);
        }
        //$this->params = $this->_cleanInputs(json_decode($_POST));
        break;

      case 'GET':
        $this->get_vars = $this->_cleanInputs($_GET);
        break;

      default:
        throw new \Exception('Invalid Method', 405);
        break;
    }
  }

  public function processApi() {
    $endpointClass = $this->endpointNameSpace . self::kebab2StudlyCase($this->endpoint);

    if (!class_exists($endpointClass)) {
      throw new \Exception("No Endpoint - missing class =  " . $endpointClass, 404);
    }

    $configArray = array(
      'authHeader' =>     $this->authHeader,
      'method' =>         $this->method,
      'args' =>           $this->args,
      'requestBody' =>    $this->requestBody,
      'get_vars' =>       $this->get_vars,
      'post_vars' =>      $this->post_vars
    );

    $endpoint = new $endpointClass($configArray);
    return static::response($endpoint->processEndpoint());
  }

  //---------------------------------------------------------------------------
  // Protected Functions
  //---------------------------------------------------------------------------

  //---------------------------------------------------------------------------
  // Private Functions
  //---------------------------------------------------------------------------

  private function _cleanInputs($data) {
    $clean_input = Array();
    if (is_array($data)) {
      foreach ($data as $k => $v) {
        $clean_input[$k] = $this->_cleanInputs($v);
      }
    } else {
      $clean_input = trim(strip_tags($data));
    }
    return $clean_input;
  }
  //---------------------------------------------------------------------------

  private function getAuthHeader() {
    $header = null;

    if (isset($_SERVER['Authorization'])) {
      $header = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
      $header = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } else if (function_exists('apache_request_headers')) {
      $requestHeaders = apache_request_headers();
      // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
      $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
      //print_r($requestHeaders);
      if (isset($requestHeaders['Authorization'])) {
        $header = trim($requestHeaders['Authorization']);
      }
    }
    return $header;
  }
  //---------------------------------------------------------------------------

  //---------------------------------------------------------------------------
  // Static Functions
  //---------------------------------------------------------------------------

  static function _requestStatus($code) {
    $status = array(
      200 => 'OK',
      201 => 'Created',
      202 => 'Accepted',

      400 => 'Bad Request',
      401 => 'Unauthorized',
      402 => 'Payment Required',
      403 => 'Forbidden',
      404 => 'Not Found',
      405 => 'Method Not Allowed',
      406 => 'Not Acceptable',

      500 => 'Internal Server Error',
      501 => 'Not Implemented',
      503 => 'Service Unavailable',
    );
    return (isset($status[$code])) ? $status[$code] : $status[500];
  }
  //---------------------------------------------------------------------------

  static function response($data, $status = 200) {
    header("HTTP/1.1 " . $status . " " . static::_requestStatus($status));

    $dataOut = json_encode($data);
    $jsonError = json_last_error();

    if ($jsonError == JSON_ERROR_NONE) {
        return $dataOut;
    } else {
        return json_last_error_msg();
    }
  }
  //---------------------------------------------------------------------------

  static function kebab2StudlyCase($kebabValue) {
    $values = explode("-", $kebabValue);
    $studlyValue = "";
    foreach ($values as $key => $value) {
      $studlyValue .= ucfirst($value);
    }
    return $studlyValue;
  }
  //---------------------------------------------------------------------------

  static function setSecretKey($value) {
    static::$secretKey = $value;
  }
  //-----------------------------------------------------------------------------

}

// for PHP < v5.5.0
if (!function_exists('json_last_error_msg')) {
  function json_last_error_msg() {
    static $ERRORS = array(
      JSON_ERROR_NONE => 'No error',
      JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
      JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
      JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
      JSON_ERROR_SYNTAX => 'Syntax error',
      JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
      JSON_ERROR_RECURSION => 'One or more recursive references in the value to be encoded',
      JSON_ERROR_INF_OR_NAN => 'One or more "NAN" or "INF" values in the value to be encoded',
      JSON_ERROR_UNSUPPORTED_TYPE => 'A value of a type that cannot be encoded was given'
    );

    $error = json_last_error();
    return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
  }
}

//-----------------------------------------------------------------------------
