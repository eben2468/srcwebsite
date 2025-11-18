<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
    namespace Zenoph\Notify\Compose;
    
    interface IMessageComposer {
        function setSender($sender);
        function getSender();
        function notifyDeliveries();
        function getDeliveryCallback();
        function setDeliveryCallback($url, $contentType);
        function validateDestinationSenderName($phoneNumber);
        function setMessage($message, $info = null);
        function getMessage();
        function getMessageId($phoneNumber);
        function messageIdExists($messageId);
    }