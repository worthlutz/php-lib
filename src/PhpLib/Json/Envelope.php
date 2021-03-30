<?php
namespace PhpLib\Json;

class Envelope implements \JsonSerializable {
  private $success = FALSE;
  private $message = '';
  private $payload = NULL;
  private $error   = NULL;

  public function __construct($payload, $success=FALSE) {
    if (isset($payload)) {
      $this->payload = $payload;
    }
    $this->success = $success;
  }

  public function jsonSerialize() {
    $vars = get_object_vars($this);
    return $vars;
  }

  public function setError($code, $title, $message) {
    $this->error = (object) [
      'code' => $code,
      'title' => $title,
      'message' => $message
    ];
  }

  public function setMessage($message) {
    $this->message = $message;
  }

  public function setPayload($payload) {
    $this->payload = $payload;
  }
}
