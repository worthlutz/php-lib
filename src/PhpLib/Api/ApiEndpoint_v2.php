<?php
namespace PhpLib\Api;

use PhpLib\Json\Envelope;

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
  protected $roles = NULL;

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

  public function requiresAuth() {
    return $this->authorizationRequired;
  }

  public function getRoles() {
    return $this->roles;
  }

  public function hasPublicGet() {
    return $this->publicGet;
  }
  //---------------------------------------------------------------------------
  // Abstract Functions
  //---------------------------------------------------------------------------

  // This function processes the endpoint.
  // It must be provided by the subclass.
  abstract protected function processEndpoint($userInfo);

  //---------------------------------------------------------------------------
  // protected Functions
  //---------------------------------------------------------------------------

  protected function error($message) {
    $envelope = new Envelope(null, false);
    $envelope->setMessage($message);
    return $envelope;
  }

  //---------------------------------------------------------------------------
  // Static Functions
  //---------------------------------------------------------------------------

  //---------------------------------------------------------------------------
  //---------------------------------------------------------------------------
}
