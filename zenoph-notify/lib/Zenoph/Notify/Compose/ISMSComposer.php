<?php

    namespace Zenoph\Notify\Compose;
    
    interface ISMSComposer {
        function setSMSType($type);
        function getSMSType();
        function getDefaultSMSType();
        function personalise();
        function getPersonalisedDestinationMessageId($phoneNumber, $values);
        function getPersonalisedDestinationWriteMode($phoneNumber, $values);
        function addPersonalisedDestination($phoneNumber, $throwEx, $values, $messageId = null);
        function personalisedValuesExists($phoneNumber, $values);
        function removePersonalisedValues($phoneNumber, $values);
        function removePersonalisedDestination($phoneNumber, $values);
        function updatePersonalisedValuesById($messageId, $newValues);
        function updatePersonalisedValues($phoneNumber, $newValues, $prevValues = null);
        function updatePersonalisedValuesWithId($phoneNumber, $newValues, $newMessageId);
        function getPersonalisedValues($phoneNumber);
        function getPersonalisedValuesById($messageId);
        function getRegisteredSenderIds();
        
        // static functions
        static function getMessageCount($message, $type);
        static function getMessageVariablesCount($messageText);
        static function getMessageVariables($messageText, $trim = false);
    }
