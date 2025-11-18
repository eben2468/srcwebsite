<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
    namespace Zenoph\Notify\Request;
    
    use Zenoph\Notify\Enums\ContentType;
    use Zenoph\Notify\Response\MessageResponse;
    
    class MessageDeliveryRequest extends NotifyRequest {
        private $_batchId = null;
        
        public function __construct($authProfile = null) {
            parent::__construct($authProfile);
        }
        
        public function setBatchId($templateId){
            if (is_null($templateId) || empty($templateId))
                throw new \Exception('Invalid message identifier for delivery request.');
            
            $this->_batchId = $templateId;
        }
        
        public function submit() {
            if (is_null($this->_batchId) || empty($this->_batchId))
                throw new \Exception('Message identifier has not been set for delivery status request.');
            
            $this->setRequestResource("report/message/delivery/{$this->_batchId}");
            $this->setResponseContentType(ContentType::GZBIN_XML);
            
            // initiate for request writing
            $this->initRequest();
            
            // submit for response
            $apiResponse = parent::submit();
            
            // create and return message response
            return MessageResponse::create($apiResponse);
        }
    }