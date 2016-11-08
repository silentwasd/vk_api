<?php

const VKAPI_SCOPE_NOTIFY = 1;
const VKAPI_SCOPE_FRIENDS = 2;
const VKAPI_SCOPE_PHOTOS = 4;
const VKAPI_SCOPE_AUDIO = 8;
const VKAPI_SCOPE_VIDEO = 16;
const VKAPI_SCOPE_PAGES = 128;
const VKAPI_SCOPE_MENU = 256;
const VKAPI_SCOPE_STATUS = 1024;
const VKAPI_SCOPE_NOTES = 2048;
const VKAPI_SCOPE_MESSAGES = 4096;
const VKAPI_SCOPE_WALL = 8192;
const VKAPI_SCOPE_ADS = 32768;
const VKAPI_SCOPE_OFFLINE = 65536;
const VKAPI_SCOPE_DOCS = 131072;
const VKAPI_SCOPE_GROUPS = 262144;
const VKAPI_SCOPE_NOTIFICATIONS = 524288;
const VKAPI_SCOPE_STATS = 1048576;
const VKAPI_SCOPE_EMAIL = 4194304;
const VKAPI_SCOPE_MARKET = 134217728;
const VKAPI_SCOPE_ALL = 140492191;

class VKAPI {
    static public $appID;
    static public $redirectURL = "https://oauth.vk.com/blank.html";
    static public $scope;
    static public $accessToken;
    static public $expiresTime;
    static public $userID;

    static private $authURL = "https://oauth.vk.com/authorize";
    static private $queryURL = "https://api.vk.com/method/";
    static private $display = "popup";
    static private $responseType = "token";
    static private $version = "5.60";

    static function genScope() {
        $args = func_get_args();
        $scope = 0;
        foreach ($args as $arg) {
            $scope += $arg;
        }
        self::$scope = $scope;
    }

    static function getAuthURL() {
        $authURL = self::$authURL;
        $redirectURL = self::$redirectURL;
        $appID = self::$appID;
        $display = self::$display;
        $scope = self::$scope;
        $responseType = self::$responseType;
        $version = self::$version;

        $assoc = array(
            'client_id' => $appID,
            'redirect_uri' => $redirectURL,
            'display' => $display,
            'scope' => $scope,
            'response_type' => $responseType,
            'v' => $version,
        );

        $url = "$authURL?" . http_build_query($assoc, '', '&');
        return $url;
    }

    static function parseAuth($url) {
        $parsed = parse_url($url);
        $params = $parsed['fragment'];
        if (preg_match('/^access_token=([^&]+)&expires_in=([^&]+)&user_id=([^&]*)$/', $params, $matches)) {
            self::$accessToken = $matches[1];
            self::$expiresTime = $matches[2];
            self::$userID = $matches[3];
            return true;
        }
        else {
            if (preg_match('/^error=([^&]+)&error_description=(.*)/', $params, $matches)) {
                if ($matches[2]) throw new Exception(urldecode($matches[2]), 1000);
                else throw new Exception($matches[1], 1000);
            }
            else throw new Exception("Не удалось вытащить из URL данные!", 1001);
        }
    }

    static function execQuery($method, $params = null, $assocOut = false, $useAccessToken = true) {
        $queryURL = self::$queryURL;
        $accessToken = self::$accessToken;
        $version = self::$version;

        $_params = null;
        if (is_array($params)) $_params = http_build_query($params, '', '&');
        if (is_string($params)) $_params = $params;

        if ($useAccessToken) $url = "{$queryURL}{$method}?{$_params}&access_token={$accessToken}&v={$version}";
        else $url = "{$queryURL}{$method}?{$_params}&v={$version}";
        $content = file_get_contents($url);

        $json = json_decode($content, true);
        if (isset($json['error'])) throw new Exception($json['error']['error_msg'], $json['error']['error_code']);
        else {
            $json1251 = self::iconv("utf-8", "windows-1251//IGNORE", $json);
            if ($assocOut) return $json1251;
            else return (object) $json1251;
        }
    }

    static function iconv($charsetIn, $charsetOut, $array) {
        $_array = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) $_array[$key] = self::iconv($charsetIn, $charsetOut, $value);
            else $_array[$key] = iconv($charsetIn, $charsetOut, $value);
        }
        return $_array;
    }
}