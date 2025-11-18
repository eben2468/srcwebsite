<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
    namespace Zenoph\Notify\Response;
    
    use Zenoph\Notify\Build\Reader\MessageReportReader;
    
    class MessageResponse extends APIResponse {
        protected $_report = null;
        
        protected function __construct() {
            parent::__construct();
        }
        
        public static function isValidDataFragment(&$fragment){
            $matches = array();
            preg_match("/<data>(.*)?<\/data>/s", $fragment, $matches); 
           
            return count($matches) > 0;
        }
        
        public function getReport(){
            return $this->_report;
        }
        
        public static function create(&$apiResponse){
            $dataFragment = &$apiResponse->getDataFragment();
            $msgResponse = new MessageResponse();
            
            $msgResponse->_httpStatusCode = $apiResponse->getHttpStatusCode();
            $msgResponse->_requestHandShake = $apiResponse->getRequestHandshake();
            
            if (!is_null($dataFragment) && !empty($dataFragment)){
                // Ensure response data fragment is correct
                if (!self::isValidDataFragment($dataFragment)){
                    throw new \Exception('Invalid response data fragment.');
                }

                // extract response details
                $msgResponse->_report = self::extractReport($dataFragment);
            }
            
            return $msgResponse;
        }
        
        protected static function extractReport(&$dataFragment){
            $reportReader = new MessageReportReader();
            $reportReader->setData($dataFragment);
            
            // read and return message report
            return $reportReader->read();
        }
    }