<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
    namespace Zenoph\Notify\Build\Writer;
    
    use Zenoph\Notify\Enums\MessageCategory;
    use Zenoph\Notify\Enums\DestinationMode;
    use Zenoph\Notify\Utils\MessageUtil;
    use Zenoph\Notify\Utils\RequestUtil;
    use Zenoph\Notify\Store\PersonalisedValues;
    use Zenoph\Notify\Build\Writer\DataWriter;
    use Zenoph\Notify\Compose\MessageComposer;
    use Zenoph\Notify\Compose\SMSComposer;
    use Zenoph\Notify\Compose\Composer;
    
    abstract class KeyValueDataWriter extends DataWriter {
        const PSND_VALUES_UNIT_SEP = "__@";
        const PSND_VALUES_GRP_SEP = "__#";
        const DESTINATIONS_SEPARATOR = ",";
        
        protected $_keyValueArr = null;

        public function __construct() {
            parent::__construct();
            
            $this->_keyValueArr = array();
        }

        public function &writeSMSRequest($sc) {
            if ($sc instanceof SMSComposer === false)
                throw new \Exception('Invalid reference for writing SMS request data.');

            $store = &$this->_keyValueArr;
            
            // write message properties
            $this->writeSMSProperties($sc, $store);
            $this->writeCommonMessageProperties($sc, $store);
            
            // write destinations
            $this->writeDestinations($sc, $store);
            
            // return request data
            return $this->prepareRequestData();
        }
        
        private function writeSMSProperties($sc, &$store){
            $messageText = $sc->getMessage();
            $messageType = $sc->getSMSType();
            
            $this->appendKeyValueData($store, "text", $messageText);
            $this->appendKeyValueData($store, "type", $messageType);
            $this->appendKeyValueData($store, "sender", $sc->getSender());
            
            // message personalisation flag
            if (SMSComposer::getMessageVariablesCount($messageText) > 0){
                if (!$sc->personalise())
                    $this->appendKeyValueData ($store, "personalise", "false");
            }
        }

        private function writeVoiceMessageProperties($vmc, &$store){
            $sender = $vmc->getSender();
            $template = $vmc->getTemplateReference();
            
            if (!is_null($sender) && !empty($sender))
                $this->appendKeyValueData($store, "sender", $sender);
            
            if (!is_null($template) && !empty($template))
                $this->appendKeyValueData($store, "template", $template);
        }
        
        protected function writeVoiceMessageData($vmc, &$store){
            // message properties
            $this->writeVoiceMessageProperties($vmc, $store);
            $this->writeCommonMessageProperties($vmc, $store);
            
            // message destinations
            $this->writeDestinations($vmc, $store);
        }
        
        protected function writeCommonMessageProperties(MessageComposer $mc, &$store) {
            // if message is to be scheduled
            if ($mc->schedule()){
                $scheduleInfo = $mc->getScheduleInfo();
                $this->writeScheduleInfo($scheduleInfo[0], $scheduleInfo[1], $store);
            }
            
            // if delivery notifications are requested
            if ($mc->notifyDeliveries()){
                $notifyInfo = $mc->getDeliveryCallback();
                $this->writeCallbackInfo($notifyInfo[0], $notifyInfo[1], $store);
            }
        }
        
        protected function writeScheduleInfo($dateTime, $utcOffset, &$store) {
            // validate
            $this->validateScheduleInfo($dateTime, $utcOffset);
            
            // append data
            $this->appendKeyValueData($store, "schedule", MessageUtil::dateTimeToStr($dateTime));
            
            // if utc offset is provided we write it
            if (!is_null($utcOffset) && !empty($utcOffset))
                $this->appendKeyValueData ($store, "offset", $utcOffset);
        }
        
        protected function writeCallbackInfo($url, $contentType, &$store) {
            // validate
            $this->validateDeliveryNotificationInfo($url, $contentType);
            
            // append data
            $this->appendKeyValueData($store, "callback_url", $url);
            $this->appendKeyValueData($store, "callback_accept", RequestUtil::getDataContentTypeLabel($contentType));
        }
        
        protected function writeDestinations(Composer $mc, &$store) {
            // get destinations
            $compDestsList = $mc->getDestinations();
            
            if ($compDestsList->getCount() == 0)
                throw new \Exception('There are no items to write message destinations.');
            
            $destsStr = "";
            $valuesStr = "";
            
            foreach ($compDestsList as $compDest){
                if ($compDest->getWriteMode() == DestinationMode::DM_NONE)
                    continue;
                
                $phoneNumber = $compDest->getPhoneNumber();
                
                // validate destination sender Id
                if ($mc instanceof MessageComposer)
                    $mc->validateDestinationSenderName($phoneNumber);
                
                // other data
                $messageId = $compDest->getMessageId();
                $destData  = $compDest->getData();
                $tempDestsStr = $phoneNumber;
                
                if (!is_null($messageId) && !empty($messageId))
                    $tempDestsStr = "{$messageId}@{$phoneNumber}";
                    
                if (!is_null($destData) && $destData instanceof PersonalisedValues){
                    $valStr = $this->getPersonalisedValuesStr($destData);
                    
                    // append to the personalised values string
                    $valuesStr .= (empty($valuesStr) ? "" : self::PSND_VALUES_GRP_SEP).$valStr;
                }
                
                // update destinations str
                $destsStr .= (empty($destsStr) ? "" : self::DESTINATIONS_SEPARATOR).$tempDestsStr;
            }
            
            // append destinations
            $this->appendKeyValueData($store, "to", $destsStr);
            
            // if there are personalised values, append them too
            if (!empty($valuesStr))
                $this->appendKeyValueData ($store, "values", $valuesStr);
        }
        
        private function getPersonalisedValuesStr($pv){
            $valStr = "";
            
            foreach ($pv->export() as $value)
                $valStr .= (empty($valStr) ? "" : self::PSND_VALUES_UNIT_SEP).$value;
            
            // return values
            return $valStr;
        }
        
        public function &writeDestinationsDeliveryRequest($messageIdsArr) {
            // message Ids
            if (is_null($messageIdsArr) || !is_array($messageIdsArr) || count($messageIdsArr) == 0)
                throw new \Exception('Invalid reference to list for writing destinations delivery request.');
            
            $store = &$this->_keyValueArr;
            $idsStr = "";

            foreach ($messageIdsArr as $messageId)
                $idsStr .= (empty($idsStr) ? "" : self::DESTINATIONS_SEPARATOR).$messageId;
            
            // append message Ids
            $this->appendKeyValueData($store, "to", $idsStr);
            
            // return request body string
            return $this->prepareRequestData();
        }
        
        public function &writeDestinationsData($mc) {
            if (is_null($mc) || $mc instanceof Composer === false)
                throw new \Exception('Invalid object reference for writing message destinations data.');

            $this->writeDestinations($mc, $this->_keyValueArr);
            
            // return it
            return $this->prepareRequestData();
        }
        
        public function &writeScheduledMessagesLoadRequest($filter) {
            // perform validation
            $this->validateScheduledMessagesLoadData($filter);
            
            $store = &$this->_keyValueArr;

            // message category to load
            if (!is_null($filter['category'])){
                $this->appendKeyValueData($store, "category", $filter['category']);
            }
            
            // date specifications
            if (!is_null($filter['dateFrom']) && !is_null($filter['dateTo'])){
                $dateFromStr = MessageUtil::dateTimeToStr($filter['dateFrom']);
                $dateToStr   = MessageUtil::dateTimeToStr($filter['dateTo']);
                
                $this->appendKeyValueData($store, "from", $dateFromStr);
                $this->appendKeyValueData($store, "to", $dateToStr);
                
                // if there is UTC offset append it
                if (!is_null($filter['offset']) || !empty($filter['offset']))
                    $this->appendKeyValueData($store, "offset", $filter['offset']);
            }
            
            // reutrn request body string
            return $this->prepareRequestData();
        }
        
        public function &writeScheduledMessageUpdateRequest($mc) {
            if (is_null($mc) || $mc instanceof Composer)
                throw new \Exception('Invalid object reference for writing scheduled message update request.');
            
            $store = &$this->_keyValueArr;
            $category = $mc->getCategory();
            
            // append template Id
            // $this->writeMessageBatchId($mc->getBatchId(), $store);
            
            // properties to be written will depend on the message category
            if ($category == MessageCategory::SMS || $category == MessageCategory::USSD)
                $this->writeSMSProperties($mc, $store);
            else
                $this->writeVoiceMessageProperties($mc, $store);
            
            // write and append message destinations if any
            if ($mc->getDestinationsCount() > 0)
                $this->writeScheduledMessageDestinations($mc, $store);
            
            // return request string
            return $this->prepareRequestData();
        }
        
        private function writeScheduledMessageDestinations(MessageComposer $mc, &$store){
            $compDestsList = $mc->getDestinations();
            
            if (is_null($compDestsList) || $compDestsList->getCount() == 0)
                return;
            
            $addDestStr = "";
            $addValuesStr = "";
            $updateDestStr = "";
            $updateValuesStr = "";
            $deleteDestStr = "";
            
            foreach ($compDestsList as $compDest){
                $destMode = $compDest->getWriteMode();
                
                // interested in destinations that have been added, updated, or to be deleted
                if ($destMode == DestinationMode::DM_NONE)
                    continue;
                
                $phoneNumber = $compDest->getPhoneNumber();
                $mc->validateDestinationSenderName($phoneNumber);
                
                // other data
                $destData = $compDest->getData();
                $messageId = $compDest->getMessageId();
                
                switch ($destMode){
                    case DestinationMode::DM_ADD:
                        $tempStr = $phoneNumber.(!is_null($messageId) && !empty($messageId) ? "@{$messageId}" : "");
                        $addDestStr .= (empty($addDestStr) ? "" : self::DESTINATIONS_SEPARATOR).$tempStr;
                        
                        // check for personalised values
                        if (!is_null($destData) && $destData instanceof PersonalisedValues){
                            $valStr = $this->getPersonalisedValuesStr($destData);
                            
                            // append
                            $addValuesStr .= (empty($addValuesStr) ? "" : self::PSND_VALUES_GRP_SEP).$valStr;
                        }
                        break;
                    
                    case DestinationMode::DM_UPDATE:
                        // the update can be phone number or in the case of text messages, the personalised values.
                        // So here the main key will be the message id
                        $updateDestStr .= (empty($updateDestStr) ? "" : self::DESTINATIONS_SEPARATOR)."{$messageId}@{$phoneNumber}";
                        
                        // check for personalised values
                        if (!is_null($destData) && $destData instanceof PersonalisedValues){
                            $valStr = $this->getPersonalisedValuesStr($destData);
                            
                            // append
                            $updateValuesStr .= (empty($updateValuesStr) ? "" : self::PSND_VALUES_GRP_SEP).$valStr;
                        }
                        break;
                    
                    case DestinationMode::DM_DELETE:
                        if (!is_null($messageId) && !empty($messageId))
                            $deleteDestStr .= (empty($deleteDestStr) ? "" : self::DESTINATIONS_SEPARATOR).$messageId;
                        break;
                }
            }
            
            // update those with data
            if (!empty($addDestStr)) {
                $this->appendKeyValueData($store, "to-add", $addDestStr);
                
                if (!empty($addValuesStr))
                    $this->appendKeyValueData($store, "values-add", $addValuesStr);
            }
            
            if (!empty($updateDestStr)){
                $this->appendKeyValueData($store, "to-update", $updateDestStr);
                
                if (!empty($updateValuesStr))
                    $this->appendKeyValueData ($store, "values-update", $updateValuesStr);
            }
            
            if (!empty($deleteDestStr))
                $this->appendKeyValueData($store, "to-delete", $deleteDestStr);
        }
        
        public function &writeUSSDRequest($ucArr) {
            
        }
        
        private function writeUSSDData($tmc, &$store){
            // in future implementation
        }
 
        protected function &prepareRequestData(){
            $requestDataArr = array('keyValues'=> $this->_keyValueArr);
            
            // return it
            return $requestDataArr;
        }
        
        protected function appendKeyValueData(&$store, $key, $value) {
            $store[$key] = $value;
        }
    }