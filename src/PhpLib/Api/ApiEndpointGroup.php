<?php
namespace PhpLib\Api;

use PhpLib\Api\Api;
use PhpLib\Api\ApiEndpoint;

// This class allows grouping endpoints into nested directories.

abstract class ApiEndpointGroup extends ApiEndpoint{

  // extending class MUST define the following:
  //    a static '$namespace' property
  //    a static 'groupName' property
  //
  // example Endpoint Group:
  //    namespace MapClickApi\endpoints;
  //    use PhpLib\Api\ApiEndpointGroup;
  //
  //    class Eoc extends ApiEndpointGroup {
  //      static $namespace = __NAMESPACE__;
  //      static $groupName = "eoc";  // this is the directory for the endpoints
  //    }

  static $namespace = __NAMESPACE__;
  static $groupName = "<groupName is not defined>";

  public function processEndpoint() {
    // TODO: is check for auth desired here?
    //       it would make auth required for whole group

    // endpoint is next argument
    $this->endpoint = array_shift($this->args);
    if (is_null($this->endpoint)) {
      throw new \Exception("No Endpoint argument", 400);
    }

    $endpointNamespace = static::$namespace . "\\" . static::$groupName . "\\";
    $endpointClass = $endpointNamespace . Api::kebab2StudlyCase($this->endpoint);

    if (!class_exists($endpointClass)) {
      throw new \Exception("No Endpoint - missing class =  " . $endpointClass, 400);
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
