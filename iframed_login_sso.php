<?php

/**
 * Roundcube Webmail iframe SSO connector 
 * This performs an automatic login if jwt token is taken by POST or GET request
 *
 * @license GNU GPLv3+
 * @author Samoilov Your aka drlight
 * @version 0.1
 */

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


class iframed_login_sso extends rcube_plugin
{


    public $task = 'login|logout|settings|mail|addressbook|calendar';

    /**
     * Plugin initialization
     */
    public function init()
    {

        // load config
        $this->load_config('config.inc.php.dist');
        $this->load_config('config.inc.php');
        $RCMAIL = rcmail::get_instance();

        // get webmail Location URL from $_SERVER

        $cur_URL = (empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ? empty($_SERVER['HTTPS']) ? 'http' : 'https' : 'https')  . "://".$_SERVER['HTTP_HOST'];

        // check if RC is opened in iframe
        if( isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe' ) {
            $iframed = true;
        } else {
            $iframed = false;
        }

        if (isset($_COOKIE["autologin"])&&($RCMAIL->config->get('hide_logout'))&&$iframed) {
            //$this->add_hook('ready', [$this, 'hide_logout']);
            $this->include_stylesheet('hide_logout.css');
        }

        if (isset($_COOKIE["autologin"])&&($RCMAIL->config->get('show_default_logo'))&&$iframed) {
            $this->add_hook('ready', [$this, 'show_default_logo']);
        }
        
        if (isset($_COOKIE["autologin"])&&($RCMAIL->config->get('hide_logo'))&&$iframed) {
            $this->include_stylesheet('hide_logo.css');
        }
        
        // add some styling fixes
        $this->include_stylesheet('style.css');


        // init application, start session, init output class, etc.
        $this->add_hook('startup', [$this, 'startup']);
        $this->add_hook('authenticate', [$this, 'authenticate']);
        $this->add_hook('logout_after', [$this, 'logout']);

    }

    /**
     * 'startup' hook handler
     *
     * @param array $args Hook arguments
     *
     * @return array Hook arguments
     */
    function startup($args)
    {
        $RCMAIL = rcmail::get_instance();
        if (!empty($_SESSION['user_id']) && !empty($_REQUEST['_autologin']) ) {
            // purge the session in case of new login when a session already exists
            $RCMAIL->kill_session();
        }
        // change action to login
        if (empty($_SESSION['user_id']) && !empty($_REQUEST['_autologin']) ) {
            $args['action'] = 'login';
        }
        return $args;
    }

    /**
     * 'authenticate' hook handler
     *
     * @param array $args Hook arguments
     *
     * @return array Hook arguments
     */
    function authenticate($args)
    {
        // include environment
        require_once 'program/include/iniset.php';
        $RCMAIL = rcmail::get_instance(0, $GLOBALS['env']);
        $request_valid = !empty($_SESSION['temp']) && $RCMAIL->check_request();
        // Get from config
        $this->dovecot_impersonate_login = $RCMAIL->config->get('dovecot_impersonate_login');
        $this->dovecot_impersonate_separator = $RCMAIL->config->get('dovecot_impersonate_separator');
        $this->dovecot_impersonate_password = $RCMAIL->config->get('dovecot_impersonate_password');
        $this->key = $RCMAIL->config->get('key');
        $this->leeway = $RCMAIL->config->get('leeway');
        $this->jwt_req_parameter = $RCMAIL->config->get('jwt_req_parameter');
        $this->jwt_login_claim = $RCMAIL->config->get('jwt_login_claim');

        Firebase\JWT\JWT::$leeway = $this->leeway;

        if (!empty($_REQUEST['_autologin'])) {

            $jwt = rawurldecode($_REQUEST[$this->jwt_req_parameter]);

            // dovecot impersonate plugin process

            try {
                    $keyO = new Key($this->key, 'ES256');
                    $decoded = JWT::decode($jwt, $keyO);
                    //print_r($decoded);
            } catch (\Throwable $e) {
                    echo 'Error: "'.$e->getMessage().'"';
                    exit;
            }

            

            $args['user']        = get_object_vars(get_object_vars($decoded)['userdata'])[$this->jwt_login_claim].$this->dovecot_impersonate_separator.$this->dovecot_impersonate_login;
            $args['pass']        = $this->dovecot_impersonate_password;
            $args['host']        = $RCMAIL->autoselect_host();
            $args['cookiecheck'] = true;
            $args['valid']       = $request_valid;

            setcookie ('autologin','YES',time()+3600);
        }

        return $args;
    }

    /**
     * remove autologin cookie
     *
     * @return nothing
     */
    function logout($args)
    {
        setcookie ('autologin','',time()-3600);
    }

    /**
     * hides logout button
     *
     * @return nothing
     */
    /*function hide_logout()
    {
        $RCMAIL = rcmail::get_instance(0, $GLOBALS['env']);
        $RCMAIL->config->set('disabled_actions', 'logout');
        
    }*/
    /**
     * shows default logo
     *
     * @return nothing
     */
    function show_default_logo()
    {
        $RCMAIL = rcmail::get_instance(0, $GLOBALS['env']);
        $RCMAIL->config->set('skin_logo', null);
        
    }
}
