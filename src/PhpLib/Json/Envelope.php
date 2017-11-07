<?php
namespace PhpLib\Json;

class Envelope {
  public $success = FALSE;
  public $message = '';
  public $payload = NULL;

  public function __construct($payload, $success=FALSE) {
      if (isset($payload)) {
        $this->payload = $payload;
      }
      $this->success = $success;
  }

  public function setMessage($message) {
    $this->message = $message;
  }
}
