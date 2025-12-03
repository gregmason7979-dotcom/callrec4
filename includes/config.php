<?php
error_reporting(E_ALL);
session_start();
/* ini_set('display_errors', '1'); */
date_default_timezone_set('Australia/Sydney');
define('username','sa');
define('password','$olidus');
define('host','192.168.1.154');
define('dbname','nextccdb');
define('adminusername','Supervisor');
define('adminpassword','WAF1234');
define('maindirectory','C:\Program Files (x86)\Mitel\MiCC Enterprise\Services\SeCRecord\-1');

define('recording_base_url','http://192.168.1.154/SeCRecord');

include('functions.php');


$model	=	new model();

?>