<?php
namespace PhpLib\Api;

use PhpLib\Api\Api_v2;
use PhpLib\Json\Envelope;
use Firebase\JWT\JWT;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;


abstract class ApiEndpoint_v2 {

    /**
   * Property: authorizationRequired
   * A boolean indicating that this endpoint is a protected endpoint.
   */
  protected $authorizationRequired = FALSE;

  /**
   * Property: roles
   * An array of valid roles for this endpoint.
   * A protected endpoint should define this array.
   */
  protected $roles = [];

  /**
   * Property: publicGet
   * A boolean indicating that GET requests are public
   * while requests with other methods are secure.
   */
  protected $publicGet = FALSE;

  /**
   * Property: method
   * The HTTP method this request was made in, either GET, POST, PUT or DELETE
   */
  protected $method = '';

  /**
   * Property: args
   * Any additional URI components after the endpoint and verb have been removed, in our
   * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
   * or /<endpoint>/<arg0>
   */
  protected $args = Array();

  /**
   * Property: file
   * Stores the input of the PUT request
   */
   protected $file = Null;

  /**
   * Constructor: __construct
   *
   */
  public function __construct($configArray) {
    $this->authHeader   = $configArray['authHeader'];
    $this->method       = $configArray['method'];
    $this->args         = $configArray['args'];
    $this->requestBody  = $configArray['requestBody'];
    $this->get_vars     = $configArray['get_vars'];
    $this->post_vars    = $configArray['post_vars'];
  }

/*  TODO: are  these needed?
  public function requiresAuth() {
    return $this->authorizationRequired;
  }

  public function getRoles() {
    return $this->roles;
  }

  public function hasPublicGet() {
    return $this->publicGet;
  }
*/

  //---------------------------------------------------------------------------
  // protected Functions
  //---------------------------------------------------------------------------

  // This function processes the endpoint. It must be extended by the endpoint
  // subclass.
  // IMPORTANT: the extended function MUST call PARENT::processEndpoint()
  // for authorization to work
  protected function processEndpoint() {
    // TODO: figure out public GET here
    //AND !($this->method == 'GET' AND $endpoint->hasPublicGet())

    if ($this->authorizationRequired AND !$this->isAuthorized($this->roles)) {
      throw new \Exception("Unauthorized", 401);
    }
  }

  // TODO: remove this and use exceptions?
  //       or is this for errors where success: false is needed?
  protected function error($message) {
    $envelope = new Envelope(null, false);
    $envelope->setMessage($message);
    return $envelope;
  }

  protected function isAuthorized($validRoles) {
    if (!$this->authHeader OR empty($this->authHeader)) {
      throw new \Exception('No Authorization Header.', 401);
    }

    $jwt = $this->getBearerToken();
    if (is_null($jwt)) {
      throw new \Exception("No JWT in Header", 401);
    }

    // debug - makes JWT wrong number of segments
    //$pos = strpos($jwt, '.');
    //$pos = strpos($jwt, '.', $pos + 3);
    //$jwt = substr($jwt, 0, $pos-2);

    $errorMsgPrefix = "JWT decode error: ";
    try {
      $decoded = JWT::decode($jwt, Api_v2::$secretKey, array('HS512'));

      // debug
      //$decoded = JWT::decode($jwt, 'bad key', array('HS512'));  // SignatureInvalidException
      //$decoded = JWT::decode($jwt, Api_v2::$secretKey);         // UnexpectedValueException

      // TODO: compare serverName with decoded->data->iss?
      //       to make sure JWT is from same server?
      //$serverName = $_SERVER['SERVER_NAME'];

      foreach ($decoded->data->roles as $role) {
        if (in_array($role, $validRoles)) {
          return true;
        }
      }
      return false;

    // TODO: fix messages here to more helpful
    } catch (ExpiredException $e) {
      // TODO: handle JWT expired
      $errorMsgPrefix .= $e->getMessage();
      throw new \Exception($errorMsgPrefix, 401);

    } catch (BeforeValidException $e) {
      // TODO: handle being used before 'nbf' or 'iat'
      $errorMsgPrefix .= $e->getMessage();
      throw new \Exception($errorMsgPrefix, 401);

    } catch (SignatureInvalidException $e) {
      // TODO: handle invalid signature
      $errorMsgPrefix .= $e->getMessage();
      throw new \Exception($errorMsgPrefix, 401);

    } catch (\UnexpectedValueException $e) {
      // TODO: handle invalid jwt
      $errorMsgPrefix .= $e->getMessage();
      throw new \Exception($errorMsgPrefix, 401);

    } catch (\InvalidArgumentException $e) {
      // TODO: handle invalid jwt
      $errorMsgPrefix .= $e->getMessage();
      throw new \Exception($errorMsgPrefix, 401);

    } catch (\DomainException $e) {
      // TODO: handle invalid jwt
      $errorMsgPrefix .= $e->getMessage();
      throw new \Exception($errorMsgPrefix, 401);

    } catch (\Exception $e) {
      echo ($e->getMessage());
      $errorMsgPrefix .= $e->getMessage();
      throw new \Exception($errorMsgPrefix, 401);
    }
  }

  //---------------------------------------------------------------------------
  // private Functions
  //---------------------------------------------------------------------------

  private function getBearerToken() {
    $token = NULL;
    if (!empty($this->authHeader)) {
      $parts = explode(' ', $this->authHeader);
      $token = $parts[1];
    }
    return $token;
  }

  //---------------------------------------------------------------------------
  //---------------------------------------------------------------------------
  //---------------------------------------------------------------------------
}
