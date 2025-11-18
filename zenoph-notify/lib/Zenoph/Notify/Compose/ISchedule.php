<?php

    namespace Zenoph\Notify\Compose;
    
    interface ISchedule {
        function getBatchId();
        function schedule();
        function isScheduled();
        function getScheduleInfo();
        function setScheduleDateTime($dateTime, $val1 = null, $val2 = null);
        function refreshScheduledDestinationsUpdate($destsList);
    }