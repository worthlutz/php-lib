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
   * Property: jwtPayload
   * payload from decoding the JWT in the authorization header
   */
  protected $jwtPayload = NULL;

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

  //---------------------------------------------------------------------------
  // protected Functions
  //---------------------------------------------------------------------------

  // This function processes the endpoint. It must be extended by the endpoint
  // subclass.
  // IMPORTANT: the extended function MUST call PARENT::processEndpoint()
  // for authorization to work
  protected function processEndpoint() {
    if ($this->authorizationRequired) {

      //if (!($this->isAuthorized() OR ($this->method == 'GET' AND $this->publicGet))) {
      if (!$this->isAuthorized() AND !($this->method == 'GET' AND $this->publicGet)) {
        throw new \Exception("Unauthorized", 401);
      }
    }
  }

  protected function isAuthorized() {
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
      $this->jwtPayload = JWT::decode($jwt, Api_v2::$secretKey, array('HS512'));

      // debug
      //$this->jwtPayload = JWT::decode($jwt, 'bad key', array('HS512'));  // SignatureInvalidException
      //$this->jwtPayload = JWT::decode($jwt, Api_v2::$secretKey);         // UnexpectedValueException

      // TODO: compare serverName with decoded->data->iss?
      //       to make sure JWT is from same server?
      //$serverName = $_SERVER['SERVER_NAME'];

      return true;

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
      //echo ($e->getMessage());
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
