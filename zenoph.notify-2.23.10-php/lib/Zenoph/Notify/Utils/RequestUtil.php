<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
    namespace Zenoph\Notify\Utils;
    
    use Zenoph\Notify\Enums\ContentType;
    
    final class RequestUtil {
        private static $DCTL_APPLICATION_XML;
        private static $DCTL_APPLICATION_JSON;
        private static $DCTL_APPLICATION_URL_ENCODED;
        private static $DCTL_MULTIPART_FORM_DATA;
        private static $DCTL_APPLICATION_GZBIN_XML;
        private static $DCTL_APPLICATION_GZBIN_JSON;
        private static $DCTL_APPLICATION_GZBIN_URL_ENCODED;
        
        public static function initShared() :void {
            self::$DCTL_APPLICATION_XML = 'application/xml';
            self::$DCTL_APPLICATION_JSON = 'application/json';
            self::$DCTL_APPLICATION_URL_ENCODED = 'application/x-www-form-urlencoded';
            self::$DCTL_MULTIPART_FORM_DATA = 'multipart/form-data';
            self::$DCTL_APPLICATION_GZBIN_XML = 'application/vnd.zenoph.zbm.gzbin+xml';
            self::$DCTL_APPLICATION_GZBIN_JSON = 'application/vnd.zenoph.zbm.gzbin+json';
            self::$DCTL_APPLICATION_GZBIN_URL_ENCODED = 'application/vnd.zenoph.zbm.gzbin+urlencoded';
        }
        
        public static function isValidContentTypeLabel($label) :bool{
            if (is_null($label) || empty($label))
                throw new \Exception('Invalid content type label for verification.');
            
            switch ($label){
                case self::$DCTL_APPLICATION_XML:
                case self::$DCTL_APPLICATION_GZBIN_XML:
                case self::$DCTL_APPLICATION_JSON:
                case self::$DCTL_APPLICATION_GZBIN_JSON:
                case self::$DCTL_APPLICATION_URL_ENCODED:
                case self::$DCTL_APPLICATION_GZBIN_URL_ENCODED:
                case self::$DCTL_MULTIPART_FORM_DATA:
                    return true;
                    
                default:
                    return false;
            }
        }
        
        public static function getDataContentTypeLabel($type) :string {
            switch ($type){
                case ContentType::XML:
                    return self::$DCTL_APPLICATION_XML;
                    
                case ContentType::JSON:
                    return self::$DCTL_APPLICATION_JSON;
                    
                case ContentType::WWW_URL_ENCODED:
                    return self::$DCTL_APPLICATION_URL_ENCODED;
                    
                case ContentType::MULTIPART_FORM_DATA:
                    return self::$DCTL_MULTIPART_FORM_DATA;
                    
                case ContentType::GZBIN_XML:
                    return self::$DCTL_APPLICATION_GZBIN_XML;
                
                case ContentType::GZBIN_JSON:
                    return self::$DCTL_APPLICATION_GZBIN_JSON;
                    
                case ContentType::GZBIN_WWW_URL_ENCODED:
                    return self::$DCTL_APPLICATION_GZBIN_URL_ENCODED;
                    
                default:
                    throw new \Exception('Unknown data content type identifier for label.');
            }
        }
        
        public static function getDataContentTypeFromLabel($label) :int {
            switch ($label){
                case self::$DCTL_APPLICATION_XML:
                    return ContentType::XML;
                    
                case self::$DCTL_APPLICATION_JSON:
                    return ContentType::JSON;
                    
                case self::$DCTL_APPLICATION_URL_ENCODED:
                    return ContentType::WWW_URL_ENCODED;
                    
                case self::$DCTL_MULTIPART_FORM_DATA:
                    return ContentType::MULTIPART_FORM_DATA;
                    
                case self::$DCTL_APPLICATION_GZBIN_XML:
                    return ContentType::GZBIN_XML;
                    
                case self::$DCTL_APPLICATION_GZBIN_JSON:
                    return ContentType::GZBIN_JSON;
                    
                case self::$DCTL_APPLICATION_GZBIN_URL_ENCODED:
                    return ContentType::GZBIN_WWW_URL_ENCODED;
                    
                default:
                    throw new \Exception('Unknown label for data content type identifier.');
            }
        }
        
        public static function &compressData(&$dataStr){
            if (is_null($dataStr) || empty($dataStr))
                throw new \Exception('Invalid data stream for compression.');
            
            $gzData = base64_encode(gzencode($dataStr, 6));

            // return compressed data
            return $gzData;
        }
        
        public static function &decompressData(&$dataStr) :string {
            if (is_null($dataStr) || empty($dataStr))
                throw new \Exception('Invalid data stream for decompression.');

            // first, decode from base64
            $decoded = gzdecode(base64_decode($dataStr));
            
            // return decoded data
            return $decoded;
        }
    }
    
    RequestUtil::initShared();