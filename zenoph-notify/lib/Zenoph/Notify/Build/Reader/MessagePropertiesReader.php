<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
    namespace Zenoph\Notify\Build\Reader;
    
    use Zenoph\Notify\Store\AuthProfile;
    use Zenoph\Notify\Utils\MessageUtil;
    use Zenoph\Notify\Enums\MessageCategory;
    use Zenoph\Notify\Compose\SMSComposer;
    use Zenoph\Notify\Compose\VoiceComposer;
    use Zenoph\Notify\Compose\MessageComposer;
    use Zenoph\Notify\Collections\MessageComposerList;
    
    class MessagePropertiesReader {
        private $_authProfile;
        private $_fragment;
        private $_xmlReader;
        private $_isScheduled;
        private $_done;
        
        public function __construct() {
            $this->_done = false;
            $this->_xmlReader = null;
            $this->_fragment = null;
            $this->_authProfile = null;
            $this->_isScheduled = true; // by default assume reading loaded scheduled message
        }
        
        public function setAuthProfile($ap){
            if (!is_null($ap)){
                if ($ap instanceof AuthProfile == false)
                    throw new \Exception('Invalid reference to authentication profile object.');
                
                $this->_authProfile = $ap;
            }
        }
        
        public function setDataFragment(&$xmlFragment){
            $this->_xmlReader = new \XMLReader();
            $this->_xmlReader->XML($xmlFragment);
        }
        
        public function isScheduled($scheduled){
            if (is_null($scheduled) || !is_bool($scheduled))
                throw new \Exception('Invalid for scheduled message specifier.');
            
            $this->_isScheduled = $scheduled;
        }
        
        private function readMessage($xmlReader){
            $messageComposer = null;
            
            // cursor should be on a message node before reading
            if ($xmlReader->nodeType == \XMLReader::ELEMENT && strtolower($xmlReader->name) === 'message'){
                $messageProps = new \SimpleXMLElement($xmlReader->readOuterXml());
                
                $batchId = (string)$messageProps->batch;
                $category = MessageUtil::messageCategoryToEnum((string)$messageProps->category);
                
                // parameters for initialising message container
                $params['batch'] = $batchId;
                $params['scheduled'] = $this->_isScheduled;
                $params['category']  = $category;
                
                if (!is_null($this->_authProfile))
                    $params['authProfile'] = $this->_authProfile;

                if ($category == MessageCategory::SMS){
                    $params['category'] = $category; 

                    $messageComposer = SMSComposer::create($params);
                    $this->setTextMessageProperties($messageComposer, $messageProps);
                }
                else if ($category == MessageCategory::VOICE){
                    $messageComposer = VoiceComposer::create($params);
                    $this->setVoiceMessageProperties($messageComposer, $messageProps);
                }

                // common message properties
                $this->setCommonMessageProperties($messageComposer, $messageProps);
            }
            
            return $messageComposer;
        }
        
        private function setTextMessageProperties($messageContainer, $xmlObj){
            $messageType = (int)$xmlObj->type;
            $messageText = (string)$xmlObj->text;
            $isPersonalised = false;

            // It could be a personalised one if it contains variables.
            if (isset($xmlObj->personalise)){
                $isPsnd = (string)$xmlObj->personalise;
                $isPersonalised = strtolower($isPsnd) === 'true' ? true : false;
            }

            $messageContainer->setPersonalisedMessage($messageText, $isPersonalised);
            $messageContainer->setSMSType($messageType);
        }
        
        private function setVoiceMessageProperties($messageContainer, $xmlReader){
            
        }
        
        private function setCommonMessageProperties($mc, $xmlObj){
            if ($mc instanceof MessageComposer) {
                // Schedule dateTime and UTC offset
                $scheduleDateTimeStr = (string)$xmlObj->schedule->dateTime;
                $scheduleUtcOffset   = (string)$xmlObj->schedule->offset;

                // format date and time
                $format = MessageUtil::DATETIME_FORMAT;
                $scheduleDateTime = \DateTime::createFromFormat($format, "{$scheduleDateTimeStr}");
                $mc->setScheduleDateTime($scheduleDateTime, $scheduleUtcOffset);
                
                // If there is message sender identifier
                if (isset($xmlObj->sender)){
                    $mc->setSender((string)$xmlObj->sender);
                }
            }

            // if there is notify URL given
            if (isset($xmlObj->callback)){
                $callbackURL = (string)($xmlObj->callback->url);
                $callbackAccept = (string)$xmlObj->callback->_accept;
                $mc->setDeliveryCallback($callbackURL, $callbackAccept);
            }
        }
        
        private function getNextMessage(){
            while (!$this->_done && $this->_xmlReader->read()){
                $nodeType = $this->_xmlReader->nodeType;
                $name = $this->_xmlReader->name;
                
                switch ($nodeType){
                    case \XMLReader::ELEMENT:
                        if (strtolower($this->_xmlReader->name) === 'message')
                            return $this->readMessage ($this->_xmlReader);
                        break;
                        
                    case \XMLReader::END_ELEMENT:
                        $name = $this->_xmlReader->name;
                        
                        if (strtolower($this->_xmlReader->name) === 'data'){
                            $this->_done = true;
                        }
                        
                        break;
                }
            }
            
            // nothing to return
            return null;
        }
        
        public function getMessages(){
            $messagesList = new MessageComposerList();
            
            while (true){
                $msgComposer = $this->getNextMessage();
                
                if (is_null($msgComposer))
                    break;
                
                // add to the collection
                $messagesList->addItem($msgComposer);
            }
            
            return $messagesList;
        }
    }