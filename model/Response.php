<?php

  class Response {
    private $_success;
    private $_httpStatusCode;
    private $_messages = array();
    private $_data;
    private $_toCache = false;
    private $_responseData = array();

    public function setSuccess($success){
      $this->_success = $success;
    }

    public function setHttpStatusCode($httpStatusCode){
      $this->_httpStatusCode = $httpStatusCode;
    }

    public function addMessage($message){
      $this->_messages[] = $message;
    }

    public function setData($data){
      $this->_data = $data;
    }

    public function toCache($toCacge){
      $this->_toCacge = $toCacge;
    }

    public function send(){
      header('Content-type: application/json; charset=utf-8');


      //Check if the contents (response need to be cached or not)
      if($this->_toCache == true){
        //Cache for 60s;
        header('Cache-control: max-age=60');
      } else {
        header('Cache-control: no-cache, no-store');
      }

      //Check if the response will be valid or not by $_success and $_httpStatusCode
      if(($this->_success !== false && $this->_success !== true) ||
          !is_numeric($this->_httpStatusCode))
      {
        //return the error messsages
        http_response_code(500);
        $this->addMessage("Response creation error");
        $this->addMessage("Test Message");

        $this->_responseData['statusCode'] = 500;
        $this->_responseData['success'] = false;
        $this->_responseData['messages'] = $this->_messages;
      } else {
        //return the successed response and data from database
        http_response_code($this->_httpStatusCode);

        $this->_responseData['statusCode'] = $this->_httpStatusCode;
        $this->_responseData['success'] = $this->_success;
        $this->_responseData['messages'] = $this->_messages;
        $this->_responseData['data'] = $this->_data;
      }

      echo json_encode($this->_responseData);

    }
  }

?>
