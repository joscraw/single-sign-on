<?php

namespace CRMConnector\CRMSingleSignOn\Frontend;

use CRMConnector\CRMSingleSignOn\Support\CRMSingleSignOn;
use WP_User;

/**
 * Class Frontend
 */
class Frontend
{
    public function __construct()
    {
        $this->template_functions();

        add_action('template_redirect', array($this, 'redirect_non_logged_in_users'));
        add_action('wp_authenticate', array($this, 'authenticated'));
        add_action('rest_api_init', array($this, 'rest_api_init'));
        add_action('user_register', array($this, 'user_registered'), 1);
    }

    /**
     * When a user is created or registers we need to
     * make sure we create the user on the other websites as well
     *
     * @param $user_id
     */
    public function user_registered($user_id)
    {
        $user = get_user_by('ID', $user_id );

        if ( is_wp_error( $user ) ) {
            return;
        }

        $options = get_option( 'crmc_settings' );
        $website_url = $options['website_url'];

        if(!$website_url)
        {
            return;
        }

        if(substr($website_url, -1) === '/')
        {
            $website_url = substr($website_url, 0, -1);
        }

        $website_url = $website_url . '/wp-json/crmc-single-sign-on/users';

        $p =  $user->data->user_pass;

        $response = wp_remote_post( $website_url, array(
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(),
                'body'        => array(
                    'user_email' => $user->data->user_email,
                    'username' => $user->data->user_login,
                    'password' => $user->data->user_pass,
                ),
                'cookies'     => array()
            )
        );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {}
    }


    /**
     * This gets called on login and logout.
     * If $username is null then the user is logging out.
     * If $username is not null then the user is logging in
     *
     * @param $username
     */
    public function authenticated($username)
    {

        if($username === null)
        {
            return;
        }

        // This could cause issues down the road if logging in from gifted hire is enabled.
        // If they login on the gifted hire side of things the access token will get overriden and
        // then they will need to login on the portal side again to be able to seemlessly go from portal to gifted hire

        $access_token = bin2hex(random_bytes(22, MCRYPT_DEV_URANDOM));
        $user = get_user_by('login', $username );

        delete_user_meta($user->data->ID, 'access_token');
        update_user_meta($user->data->ID, 'access_token', $access_token, $unique = true);

        $options = get_option( 'crmc_settings' );
        $website_url = $options['website_url'];

        if(!$website_url)
        {
            return;
        }

        if(substr($website_url, -1) === '/')
        {
            $website_url = substr($website_url, 0, -1);
        }

        $website_url = $website_url . '/wp-json/crmc-single-sign-on/set-access-token';

        $response = wp_remote_post( $website_url, array(
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(),
                'body'        => array(
                    'user_email' => $user->data->user_email ,
                    'access_token' => $access_token
                ),
                'cookies'     => array()
            )
        );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {}

    }

    public function rest_api_init()
    {
        register_rest_route( 'crmc-single-sign-on', 'set-access-token',array(

            'methods'  => 'POST',
            'callback' => array($this, 'set_access_token')

        ));

        register_rest_route( 'crmc-single-sign-on', 'users',array(

            'methods'  => 'POST',
            'callback' => array($this, 'create_user')

        ));
    }

    public function create_user($params)
    {
        $user_email = $params['user_email'];
        $username = $params['username'];
        $password = $params['password'];

        if( null == username_exists( $user_email ) ) {
            $user_id = wp_create_user ( $username, $password, $user_email );

            $user = new WP_User( $user_id );
            $user->set_role( 'Administrator' );
        }

    }


    public function set_access_token ( $params ){

        $user_email = $params['user_email'];
        $access_token = $params['access_token'];
        $user = get_user_by('email', $user_email );

        if ( !is_wp_error( $user ) )
        {
            delete_user_meta($user->data->ID, 'access_token');
            update_user_meta($user->data->ID, 'access_token', $access_token, $unique = true);
        }

    }

    public function template_functions()
    {
        global $crm_single_sign_on_link;

        /**
         * @param string $url The Unauthenticated URL to direct the user to
         * @param string $link_text The Name of the Link
         * @return string
         */
        $crm_single_sign_on_link = function($url, $link_text)
        {
            if(is_user_logged_in())
            {
                $user = wp_get_current_user();
                $access_token = get_user_meta($user->data->ID, 'access_token', true);
            }

            return sprintf('<a href="%s?access_token=%s">%s</a>',
                $url,
                isset($access_token) ? $access_token : "",
                $link_text
            );

        };
    }

    /**
     * This gets called on all NON-ADMIN pages. This method checks to see if an access token
     * exists in the URL then checks to see if it is attached to a specific user and then attempts to log
     * you in and then redirect you to the URL you would like to go to
     */
    public function redirect_non_logged_in_users()
    {
        if(isset($_GET['access_token']))
        {
            $access_token = $_GET['access_token'];
            $user = get_users(array('meta_key' => 'access_token', 'meta_value' => $access_token))[0];

            if ($user)
            {
                $actual_link = strtok((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", "?");
                unset($_GET['access_token']);
                $actual_link .= !empty($_GET) ? '?' . http_build_query($_GET) : '';

                wp_clear_auth_cookie();
                wp_set_current_user ( $user->ID );
                wp_set_auth_cookie  ( $user->ID );

                wp_safe_redirect( $actual_link );
                exit();
            }
        }
    }

}