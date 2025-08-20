<?php

require('../routeros-api/routeros_api.class.php');

$API = new RouterosAPI();

$API->debug = true;

if ($API->connect('103.148.197.14', 'noc@aqsaa.id', 'noc@aqsaa.id')) {

   $API->write('/ppp/active');

   $READ = $API->read(false);
   $ARRAY = $API->parseResponse($READ);

   print_r($ARRAY);

   $API->disconnect();

}

?>
