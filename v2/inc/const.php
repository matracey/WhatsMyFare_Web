<?php 
$http_context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
const SERVER = "http://whatsmyfare.ie";
const PATH = "/private/api/";
const API_KEY = "MzM5ODM2MzI=";
$API_URL = "http://" . SERVER . PATH . API_KEY . "/";

?>
