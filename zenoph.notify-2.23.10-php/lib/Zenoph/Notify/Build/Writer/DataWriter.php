<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

    namespace Zenoph\Notify\Build\Writer;
    
    use Zenoph\Notify\Enums\AuthModel;
    use Zenoph\Notify\Enums\ContentType;
    use Zenoph\Notify\Utils\MessageUtil;
    use Zenoph\Notify\Build\Writer\IDataWriter;
    use Zenoph\Notify\Compose\MessageComposer;
    use Zenoph\Notify\Compose\Composer;
    
    abstract class DataWriter implements IDataWriter {
        protected $_authModel;
        protected $_authApiKey;
        protected $_authLogin;
        protected $_authPassword;
        protected $_authLoadAPS;

    //    protected abstract function writeAuthData(&$store);
        protected abstract function writeDestinations(Composer $mc, &$store);
        protected abstract function writeCommonMessageProperties(MessageComposer $mc, &$store);
        protected abstract function writeScheduleInfo($dateTime, $utcOffset, &$store);
        protected abstract function writeCallbackInfo($url, $contentType, &$store);
        
        private static $_AUTH_FACTOR_SEPARATOR = "__::";
        
        public function __construct() {
            
        }
        
        public static function create($contentType){
            if ($contentType == ContentType::XML || $contentType == ContentType::GZBIN_XML)
                return new XmlDataWriter();
            else if ($contentType == ContentType::WWW_URL_ENCODED || $contentType == ContentType::GZBIN_WWW_URL_ENCODED)
                return new UrlEncodedDataWriter();
            else if ($contentType == ContentType::MULTIPART_FORM_DATA)
                return new MultiPartDataWriter();
            else
                throw new \Exception('Invalid or unsupported content type for initialising request writer.');
        }

        public function setAuthModel($model){
            if ($model == null || ($model != AuthModel::API_KEY && $model != AuthModel::PORTAL_PASS))
                    throw new \Exception("Invalid model for writing authentication data.");
            
            $this->_authModel = $model;
        }
        
        public function setAuthApiKey($key){
            if (is_null($key) || empty($key))
                throw new \Exception("Invalid API key for writing authentication data.");
            
            $this->_authApiKey = $key;
        }
        
        public function setAuthLogin($login){
            if (is_null($login) || empty($login))
                throw new \Exception("Invalid login for writing authentication data.");
            
            $this->_authLogin = $login;
        }
        
        public function setAuthPassword(&$psswd){
            if (is_null($psswd) || empty($psswd))
                throw new \Exception("Invalid password for writing authentication data.");
            
            $this->_authPassword = $psswd;
        }
        
        public function setAuthAPSLoad($load){
            if (is_null($load) || !is_bool($load))
                throw new \Exception("Invalid specifier for loading APS.");
            
            $this->_authLoadAPS = $load;
        }
     
        protected function assertAuthData(){
            if ($this->_authModel != AuthModel::API_KEY && $this->_authModel != AuthModel::PORTAL_PASS)
                throw new \Exception('Authentication model has not been set for writing request.');
            
            // If portal pass, login and password should be set
            if ($this->_authModel == AuthModel::PORTAL_PASS){
                if (is_null($this->_authLogin) || empty($this->_authLogin))
                    throw new \Exception('Account login has not been set for writing request.');
                
                if (is_null($this->_authPassword) || empty($this->_authPassword))
                    throw new \Exception('Account password has not been set for writing request.');
            }
            else {  // API key authentication then
                if (is_null($this->_authApiKey) || empty($this->_authApiKey))
                    throw new \Exception('API key has not been set for writing request.');
            }
        }
        
        protected function validateScheduleInfo($dateTime, $utcOffset){
            if (is_null($dateTime) || $dateTime instanceof \DateTime === false)
                throw new \Exception('Invalid date time object for writing message scheduling information.');
            
            if (is_null($utcOffset) || empty($utcOffset)){
                if (!MessageUtil::isValidTimeZoneOffset($utcOffset))
                    throw new \Exception('Invalid time zone UTC offset specifier.');
            }
        }
        
        protected function validateDeliveryNotificationInfo($url, $contentType){
            if (is_null($url) || empty($url))
                throw new \Exception('Invalid URL for message delivery notifications.');
            
            if ($contentType != ContentType::XML && $contentType != ContentType::JSON)
                throw new \Exception('Unsupported content type for message delivery notifications.');
        }
        
        protected function validateScheduledMessagesLoadData($dataArr){
            if (is_null($dataArr) || !is_array($dataArr))
                throw new \Exception('Invalid reference for writing scheduled messages load request.');
            
            // keys must be present, even when null
            if (!array_key_exists('category', $dataArr))
                throw new \Exception('Message category not set for writing scheduled messages request.');
            
            if (!array_key_exists('dateFrom', $dataArr))
                throw new \Exception("Date 'From' has not been set for writing scheduled messages request.");
            
            if (!array_key_exists('dateTo', $dataArr))
                throw new \Exception("Date 'To' has not been set for writing scheduled messages request.");
            
            if (!array_key_exists('offset', $dataArr))
                throw new \Exception('Time zone offset has not been set for writing scheduled messages request.');
            
            // To know if it is specific message, templateId must be present, can be null for unspecified message
            if (!array_key_exists('batch', $dataArr))
                throw new \Exception('Message batch identifier has not been set for writing scheduled messages request.');
        }
    }