<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
    namespace Zenoph\Notify\Store;
    
    use Zenoph\Notify\Enums\AuthModel;
    use Zenoph\Notify\Store\UserData;
    
    class AuthProfile {
        private $authLogin = "";
        private $authPassword = "";
        private $authApiKey = "";
        private $authModel = AuthModel::API_KEY;
        private $authed = false;
        private $userData = null;
        
        public function __construct() {
            $this->userData = array();
        }
        
        public function authenticated() :bool{
            return $this->authed;
        }
        
        public function getAuthModel(){
            return $this->authModel;
        }
        
        public function setAuthModel(string $model){
            switch ($model){
                case AuthModel::PORTAL_PASS:
                case AuthModel::API_KEY:
                    break;
                
                default:
                    throw new \Exception('Invalid authentication model specifier.');
            }
            
            // set it.
            $this->authModel = $model;
        }
        
        public function setAuthLogin(string $login){
            if (is_null($login) || empty($login))
                throw new \Exception('Missing or invalid authentication login.');
            
            $this->authLogin = $login;
        }
        
        public function getAuthLogin() :string {
            return $this->authLogin;
        }
        
        public function setAuthPassword(string $psswd) :void {
            if (is_null($psswd) || empty($psswd))
                throw new \Exception('Missing or invalid authentication password.');
            
            $this->authPassword = $psswd;
        }
        
        public function getAuthPassword() :string {
            return $this->authPassword;
        }
        
        public function setAuthApiKey(string $apiKey) :void {
            if (is_null($apiKey) || empty($apiKey))
                throw new \Exception('Missing or invalid authentication API key.');
            
            $this->authApiKey = $apiKey;
        }
        
        public function getAuthApiKey() :string {
            return $this->authApiKey;
        }
        
        public function extractUserData(&$df) :void {
            $this->userData = UserData::create($df);
            $this->authed = true;
        }
        
        public function getUserData() :UserData{
            return $this->userData;
        }
    }
