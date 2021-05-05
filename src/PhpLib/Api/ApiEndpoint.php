<?php
namespace PhpLib\Api;

use PhpLib\Api\Api;
use PhpLib\Json\Envelope;
use Firebase\JWT\JWT;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;


abstract class ApiEndpoint {

  /**
   * Property: authorizationRequired
   * A boolean indicating that this endpoint is a protected endpoint.
   */
  protected $authorizationRequired = FALSE;

  /**
   * Property: sameServerOnly
   * A boolean indicating that the JWT must be issued by this server.
   */
  protected $sameServerOnly = FALSE;

  /**
   * Property: validRoles
   * An array containing valid roles for this endpoint if authorization is required.
   */
  protected $validRoles = [];

  /**
   * Property: jwtPayload
   * payload from decoding the JWT in the authorization header
   */
  protected $jwtPayload = NULL;

  /**
   * Property: allowPublicGet
   * A boolean indicating that GET requests are public
   * while requests with other methods are secure.
   */
  protected $allowPublicGet = FALSE;

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
      // check for auth header
      if ($this->authHeader AND !empty($this->authHeader)) {
        // process header to get jwtPayload
        processAuthHeader();

        if ($this->sameServerOnly) {
          // make sure JWT is from same server
          if (!isset($this->jwtPayload['iss'])) {
            throw new \Exception("JWT is missing 'iss' claim.", 401);
          }
          if ($this->jwtPayload['iss'] !== $_SERVER['SERVER_NAME']) {
            throw new \Exception("JWT is not issued by this server.", 401);
          }
        }

        // 'aud' claim is user roles
        if (!isset($this->jwtPayload['aud'])) {
          throw new \Exception("JWT is missing 'aud' claim.", 401);
        }
        $userRoles = $this->jwtPayload['aud'];
        if (!is_array($userRoles)) {
          // make single role an array
          $userRoles = [ $userRoles ];
        }
        if (count(array_intersect($this->validRoles, $userRoles)) === 0)  {
          $message = "No valid role in user roles.";
          throw new \Exception($message, 403);  // forbidden
        }
      } else {
        // check for allowPublicGet
        if ($this->method == 'GET' AND !$this->allowPublicGet) {
          throw new \Exception("Unauthorized", 401);
        }
      }
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

  private function processAuthHeader() {
    // get token from header
    $jwt = $this->getBearerToken();
    if (is_null($jwt)) {
      throw new \Exception("No JWT in Header", 401);
    }

    // decode token and get payload
    $decodeErrorPrefix = "JWT decode error: ";
    try {
      $jwtDecoded = JWT::decode($jwt, Api_v2::$secretKey, array('HS512'));

      // debug
      //$jwtDecoded = JWT::decode($jwt, 'bad key', array('HS512'));  // SignatureInvalidException
      //$jwtDecoded = JWT::decode($jwt, Api_v2::$secretKey);         // UnexpectedValueException

      // convert obj to associative array
      $this->jwtPayload = (array) $jwtDecoded;

    // TODO: fix messages here to more helpful
    } catch (ExpiredException $e) {
      // TODO: handle JWT expired
      $decodeErrorPrefix .= $e->getMessage();
      throw new \Exception($decodeErrorPrefix, 401);

    } catch (BeforeValidException $e) {
      // TODO: handle being used before 'nbf' or 'iat'
      $decodeErrorPrefix .= $e->getMessage();
      throw new \Exception($decodeErrorPrefix, 401);

    } catch (SignatureInvalidException $e) {
      // TODO: handle invalid signature
      $decodeErrorPrefix .= $e->getMessage();
      throw new \Exception($decodeErrorPrefix, 401);

    } catch (\UnexpectedValueException $e) {
      // TODO: handle invalid jwt
      $decodeErrorPrefix .= $e->getMessage();
      throw new \Exception($decodeErrorPrefix, 401);

    } catch (\InvalidArgumentException $e) {
      // TODO: handle invalid jwt
      $decodeErrorPrefix .= $e->getMessage();
      throw new \Exception($decodeErrorPrefix, 401);

    } catch (\DomainException $e) {
      // TODO: handle invalid jwt
      $decodeErrorPrefix .= $e->getMessage();
      throw new \Exception($decodeErrorPrefix, 401);

    } catch (\Exception $e) {
      //echo ($e->getMessage());
      $decodeErrorPrefix .= $e->getMessage();
      throw new \Exception($decodeErrorPrefix, 401);
    }
  }

  //---------------------------------------------------------------------------
  //---------------------------------------------------------------------------
}
