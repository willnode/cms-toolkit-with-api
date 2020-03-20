<?php

/*
|--------------------------------------------------------------------------
| Email Config
|--------------------------------------------------------------------------
|
| This is custom config for email handling. Call send_email_for_real() with this
|
*/
$config['email']['default'] = [
	'mailtype' => 'html',
	'charset' => 'utf-8',
	'protocol' => 'smtp',
	'smtp_host' => 'SMTP_HOST', // TODO
	'smtp_user' => 'SMTP_USER', // TODO
	'smtp_pass' => 'SMTP_PASS', // TODO
	'smtp_crypto' => 'ssl',
	'smtp_port' => 465,
	'crlf' => "\r\n",
	'newline' => "\r\n",
];