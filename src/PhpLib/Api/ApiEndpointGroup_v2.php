<?php
namespace PhpLib\Api;

use PhpLib\Api\ApiEndpoint_v2;

// This class allows grouping endpoints into nested directories.

abstract class ApiEndpointGroup_v2 extends ApiEndpoint_v2{

  // extending class MUST define the following:
  //    a static '$namespace' property
  //    a protected 'groupName' property
  // LogicException will be thrown if $groupName is not defined
  static $namespace = __NAMESPACE__;
  //protected $groupName = "<groupName is not defined>";

  /**
   * Constructor: __construct
   *
   */
  final public function __construct($configArray) {
    parent::__construct($configArray);

    if (is_null($this->groupName)) {
      $errorMsg = 'No groupName property set in class: ' . get_called_class();
      throw new \LogicException($errorMsg);
    }
  }

  public function processEndpoint($userInfo) {

    // endpoint is next argument
    // TODO: check for missing endpoint!
    $this->endpoint = array_shift($this->args);

    $endpointNamespace = static::$namespace . "\\" . $this->groupName . '\\';
    $endpointClass = $endpointNamespace . ucfirst($this->endpoint);

    if (!class_exists($endpointClass)) {
      // TODO: should this throw an Exception?
      return "No Endpoint: $this->endpoint";
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

    return $endpoint->processEndpoint($userInfo);
  }
}
