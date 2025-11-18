<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
    namespace Zenoph\Notify\Build\Writer;
    
    use Zenoph\Notify\Compose\VoiceComposer;
    use Zenoph\Notify\Build\Writer\KeyValueDataWriter;
    
    class UrlEncodedDataWriter extends KeyValueDataWriter {
        public function __construct() {
            parent::__construct();
        }
        
        public function &writeVoiceRequest(VoiceComposer $vc) {
            $store = &$this->_keyValueArr;
            
            // write the voice message data
            $this->writeVoiceMessageData($vc, $store);
            
            // return request body
            return $this->prepareRequestData();
        }
    }