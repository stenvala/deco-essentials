<?php

namespace deco\essentials\rest;

class Error implements ErrorReportingInterface {

  const DEBUG_NONE = 1;
  const DEBUG_PRODUCTION = 2;
  const DEBUG_ALL = 4;

  protected $debugLevel;
  protected $exception;
  protected $service;

  public function __construct($debugLevel) {
    $this->debugLevel = $debugLevel;
  }

  public function setService($service) {
    $this->service = $service;
  }

  //public function report(\deco\essentials\exception\Base $exception) {
  public function report($exception) {
    $this->exception = $exception;
    return $this->service->finalize($this->getErrorReport());
  }

  public function getErrorReport() {
    switch ($this->debugLevel) {
      case self::DEBUG_NONE:
        $this->service->setStatusCode(400);
        return array('msg' => 'Error occurred');
      case self::DEBUG_PRODUCTION:
        return $this->getErrorReportProduction();
      case self::DEBUG_ALL;
        return $this->getErrorReportAll();
    }
  }

  public function getErrorReportProduction() {
    $code = $this->exception->getCode();
    $code = 500;
    //$this->service->setStatusCode($code);
    $debugInfo = array();
    $msg = $this->exception->getMessage();
    if ($msg != '') {
      $debugInfo['msg'] = $msg;
    }
    if (is_subclass_of($this->exception, '\deco\essentials\exception\Base')) {
      if ($this->exception->getType() == 'validation') {
        $debugInfo['validation'] = $this->exception->getValidationErrorFields();
      }
    }
    return $debugInfo;
  }

  public function getErrorReportAll() {
    $debugInfo = $this->getErrorReportProduction();
    $debugInfo['trace'] = $this->exception->getTrace();
    // $debugInfo['type'] = $this->exception->getType();    
    return $debugInfo;
  }

}
