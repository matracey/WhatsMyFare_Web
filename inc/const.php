<?php 
$http_context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
const SERVER = "localhost";
const PATH = "/~martintracey/whats_my_fare/private/api/";
const API_KEY = "MzM5ODM2MzI=";
$API_URL = "http://" . SERVER . PATH . API_KEY . "/";

?>