<?php
/**
 +-------------------------------------------------------------------------+
 | Roundcube Webmail Nextcloud SSO connector                               |
 | Author: Samoilov Your aka drlight                                       |
 | Version: 0.1                                                            |
 +-------------------------------------------------------------------------+
*/
?>
<?php

// in roundcube root folder do:
// git clone https://github.com/firebase/php-jwt 

require_once 'php-jwt/src/Key.php';
require_once 'php-jwt/src/JWTExceptionWithPayloadInterface.php';
require_once 'php-jwt/src/ExpiredException.php';
require_once 'php-jwt/src/SignatureInvalidException.php';
require_once 'php-jwt/src/BeforeValidException.php';
require_once 'php-jwt/src/JWT.php';

use Firebase\JWT\Key;
use Firebase\JWT\JWTExceptionWithPayloadInterface;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\JWT;



/* ****************** CONFIG ************************* */

// set leeway for time difference between Nextcloud and Roundcube (in seconds)
Firebase\JWT\JWT::$leeway = 5;

// get webmail Location URL from $_SERVER

$cur_URL = (empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ? empty($_SERVER['HTTPS']) ? 'http' : 'https' : 'https')  . "://".$_SERVER['HTTP_HOST'].str_replace( 'nc-login.php', '', $_SERVER['DOCUMENT_URI'] );


// nextcloud external site jwt public key, copy from nextcloud command request:
// occ config:app:get external jwt_token_pubkey_es256

$key = '-----BEGIN PUBLIC KEY-----
somekeywdkhjfgkjhwdfgkhjgkjbndfgkjh
-----END PUBLIC KEY-----';

// your dovecot impersonate master login and password
$dovecot_impersonate_login = 'master_user';
$dovecot_impersonate_separator = '*';
$dovecot_impersonate_password = 'master_password';

/* ****************** END CONFIG ************************* */


$jwt = rawurldecode($_REQUEST['jwt']);

// include environment
require_once 'program/include/iniset.php';
// init application, start session, init output class, etc.
$RCMAIL = rcmail::get_instance(0, $GLOBALS['env']);

// Make the whole PHP output non-cacheable (#1487797)
$RCMAIL->output->nocacheing_headers();
$RCMAIL->output->common_headers(!empty($_SESSION['user_id']));

$startup = $RCMAIL->plugins->exec_hook('startup', ['task' => $RCMAIL->task, 'action' => $RCMAIL->action]);
$RCMAIL->set_task($startup['task']);
$RCMAIL->action = $startup['action'];

$request_valid = !empty($_SESSION['temp']) && $RCMAIL->check_request();
$pass_charset  = $RCMAIL->config->get('password_charset', 'UTF-8');

// purge the session in case of new login when a session already exists
$RCMAIL->kill_session();

// for dovecot impersonate plugin process
try {
        $keyO = new Key($key, 'ES256');
        $decoded = JWT::decode($jwt, $keyO);
} catch (\Throwable $e) {
        echo 'Error: "'.$e->getMessage().'"';
        exit;
}

$auth = $RCMAIL->plugins->exec_hook('authenticate', [
    'host'  => $RCMAIL->autoselect_host(),
    'user'  => get_object_vars(get_object_vars($decoded)['userdata'])['email'].$dovecot_impersonate_separator.$dovecot_impersonate_login,
    'pass'  => $dovecot_impersonate_password,
    'valid' => $request_valid,
    'error' => null,
    'cookiecheck' => true,
]);

if ($RCMAIL->login($auth['user'], $auth['pass'], $auth['host'], $auth['cookiecheck'])){
        $RCMAIL->session->remove('temp');
        $RCMAIL->session->regenerate_id(false);
        // send auth cookie if necessary
        $RCMAIL->session->set_auth_cookie();
        // log successful login
        $RCMAIL->log_login();
        header('Location: '.$cur_URL);
}
?>
