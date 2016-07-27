<?php
$phpCasPath = '.';

// /////////////////////////////////////
// Basic Config of the phpCAS client //
// /////////////////////////////////////

// Full Hostname of your CAS Server
$casHost = 'my.ename.cn';

// service ID
$casServiceId = '13';

// Context of the CAS Server
$casContext = '/cas';

// Port of your CAS server. Normally for a https server it's 443
$casPort = 443;

$md5Key = 'ename2012hush13';

// Path to the ca chain that issued the cas server certificate
// $caCertPath =
// array('pem'=>'/var/www/web1.pem','crt'=>'/var/www/web1.crt','key'=>'/var/www/web1.key');
$caCertPath = '';
//$caCertPath = array('pem'=>'/var/www/ename/cascrt/manage.pem',
//					'crt'=>'/var/www/ename/cascrt/manage.crt',
//					'key'=>'/var/www/ename/cascrt/manage.key');
