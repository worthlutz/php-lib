<?php
namespace PhpLib\Api;

use PhpLib\Api\ApiEndpoint_v2;
use Firebase\JWT\JWT;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class Api_v2 {

  /**
   * Property: secretKey
   * A static string which should be set to the same key set in the
   * ApiEndpoint_v2 class so encoding and decoding JWT will work.
   * This would be done for both in the start-api-v2 program.
   */
  static $secretKey = "Api_v2_key";

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
    // TODO: is this needed?
    if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
      if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
          $this->method = 'DELETE';
      } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
          $this->method = 'PUT';
      } else {
          throw new Exception("Unexpected Header");
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
        $this->_response('Invalid Method', 405);
        break;
    }
  }

  public function processApi() {
    $endpointClass = $this->endpointNameSpace . ucfirst($this->endpoint);

    if (!class_exists($endpointClass)) {
      //throw new \Exception("No Endpoint - missing class =  " . $endpointClass, 1);
      return $this->_response("No Endpoint: $this->endpoint", 404);
    }

    $configArray = array(
      'authHeader' =>     $this->authHeader,
      'method' =>         $this->method,
      'args' =>           $this->args,
      'requestBody' =>    $this->requestBody,
      'get_vars' =>       $this->get_vars,
      'post_vars' =>      $this->post_vars
    );

    // TODO: should this be in a try/catch
    //       or should it be cought in start-api-v2?
    $endpoint = new $endpointClass($configArray);

    $userInfo = array(
      'username'  => 'anonymous',
      'fullname'  => 'anonymous',
      'roles'     => array()  // empty array is for PUBLIC data
    );

    // TODO: figure out public get here
    //AND !($this->method == 'GET' AND $endpoint->hasPublicGet())
    if (!$endpoint->requiresAuth()) {
      return $this->_response($endpoint->processEndpoint($userInfo));
    }

    // authorization header is required
    if (!$this->authHeader OR empty($this->authHeader)) {
      return $this->_response('No Authorization Header.', 401);
    }

    // extract JWT from authorization header
    list($jwt) = sscanf( $this->authHeader, 'Bearer %s');

    // debug - makes JWT wrong number of segments
    //$pos = strpos($jwt, '.');
    //$pos = strpos($jwt, '.', $pos + 3);
    //$jwt = substr($jwt, 0, $pos-2);

    // decode JWT
    try {
      //JWT::$leeway = 10;  // TODO: may not be needed except for testing

      $payload = JWT::decode($jwt, self::$secretKey, array('HS512'));
      // debug
      //$payload = JWT::decode($jwt, 'bad key', array('HS512'));  // SignatureInvalidException
      //$payload = JWT::decode($jwt, self::$secretKey);           // UnexpectedValueException

      //var_dump($payload);

    } catch (ExpiredException $e) {
      // TODO: handle JWT expired
      return $this->_response($e->getMessage(), 401);

    } catch (BeforeValidException $e) {
      // TODO: handle being used before 'nbf' or 'iat'
      return $this->_response($e->getMessage(), 401);

    } catch (SignatureInvalidException $e) {
      // TODO: handle invalid signature
      return $this->_response($e->getMessage(), 401);

    } catch (\UnexpectedValueException $e) {
      // TODO: handle invalid jwt
      return $this->_response($e->getMessage(), 400);

    } catch (\InvalidArgumentException $e) {
      // TODO: handle invalid jwt
      return $this->_response($e->getMessage(), 400);

    } catch (\DomainException $e) {
      // TODO: handle invalid jwt
      return $this->_response($e->getMessage(), 400);

    } catch (Exception $e) {
      // TODO: some other error
      return $this->_response($e->getMessage(), 400);
    }

    // debug
    //var_dump($endpoint->getRoles());
    //var_dump($payload->data->roles);

    $roles = array_values(array_intersect($endpoint->getRoles(), $payload->data->roles));
    if (count($roles) < 1) {
      // no roles in common!
      // TODO: can endpoint be partially PUBLIC?  GET only?
      return $this->_response("Unauthorized: no matching role", 405);
    }

    $userInfo = array(
      'username'  => $payload->data->username,
      'fullname'  => $payload->data->fullname,
      'roles'     => $roles
    );

    //return $this->_response('auth GOOD');
    return $this->_response($endpoint->processEndpoint($userInfo));
  }

  //---------------------------------------------------------------------------
  // Private Functions
  //---------------------------------------------------------------------------

  private function getAuthHeader() {
    $authHeader = '';
    if (function_exists('apache_request_headers')) {
      $apacheRequestHeaders = apache_request_headers();

      //echo "<br><br> " . $apacheRequestHeaders['Authorization'];
      //echo "<br><br> " . $apacheRequestHeaders['authorization'];
      //var_dump($apacheRequestHeaders);

      if (isset($apacheRequestHeaders['Authorization'])) {
          $authHeader = $apacheRequestHeaders['Authorization'];
      } elseif (isset($apacheRequestHeaders['authorization'])) {
        $authHeader = $apacheRequestHeaders['authorization'];
      }
    }

    return $authHeader;
  }
  //---------------------------------------------------------------------------

  private function _response($data, $status = 200) {
    header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));

    //return json_encode($data);
    $dataOut = json_encode($data);
    $jsonError = json_last_error();

    if ($jsonError == JSON_ERROR_NONE) {
        return $dataOut;
    } else {
        return json_last_error_msg();
    }
  }
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

  private function _requestStatus($code) {
    $status = array(
      200 => 'OK',
      201 => 'Created',
      202 => 'Accepted',
      400 => 'Bad Request',
      401 => 'Unauthorized',
      403 => 'Forbidden',
      404 => 'Not Found',
      405 => 'Method Not Allowed',
      406 => 'Not Acceptable',

      500 => 'Internal Server Error',
    );
    return ($status[$code])?$status[$code]:$status[500];
  }
  //---------------------------------------------------------------------------

  //---------------------------------------------------------------------------
  // Static Functions
  //---------------------------------------------------------------------------

  static function setSecretKey($value) {
    self::$secretKey = $value;
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
