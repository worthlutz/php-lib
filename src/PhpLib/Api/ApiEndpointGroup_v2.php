<?php
namespace PhpLib\Api;

use PhpLib\Api\ApiEndpoint_v2;

// This class allows grouping endpoints into nested directories.

abstract class ApiEndpointGroup_v2 extends ApiEndpoint_v2{

  // extending class MUST define the following:
  //    a static '$namespace' property
  //    a static 'groupName' property
  //
  // example Endpoint Group:
  //    namespace MapClickApi\endpoints_v2;
  //    use PhpLib\Api\ApiEndpointGroup_v2;
  //
  //    class Eoc extends ApiEndpointGroup_v2 {
  //      static $namespace = __NAMESPACE__;
  //      static $groupName = "eoc";  // this is the directory for the endpoints
  //    }

  static $namespace = __NAMESPACE__;
  static $groupName = "<groupName is not defined>";

  public function processEndpoint() {

    // endpoint is next argument
    // TODO: check for missing endpoint!
    $this->endpoint = array_shift($this->args);

    $endpointNamespace = static::$namespace . "\\" . static::$groupName . "\\";
    $endpointClass = $endpointNamespace . ucfirst($this->endpoint);

    if (!class_exists($endpointClass)) {
      // TODO: should this throw an Exception?
      throw new \Exception("No Endpoint - missing class =  " . $endpointClass, 1);
      //return "No Endpoint: $this->endpoint";
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

    return $endpoint->processEndpoint();
  }
}
