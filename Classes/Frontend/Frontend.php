<?php

namespace CRMConnector\CRMSingleSignOn\Frontend;

use CRMConnector\CRMSingleSignOn\Support\CRMSingleSignOn;

/**
 * Class Frontend
 */
class Frontend
{
    public function __construct()
    {
        $this->template_functions();

        // This only works on non admin pages.
        add_action('template_redirect', array($this, 'redirect_non_logged_in_users'));
        add_action('wp_authenticate', array($this, 'authenticated'));
        add_action('rest_api_init', array($this, 'rest_api_init'));
    }


    public function authenticated($username)
    {
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

        $website_url = $website_url . 'wp-json/crmc-single-sign-on/set-access-token';


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
        } else {
            /*echo 'Response:<pre>';
            print_r( $response );
            echo '</pre>';*/
        }

    }

    public function rest_api_init()
    {
        register_rest_route( 'crmc-single-sign-on', 'set-access-token',array(

            'methods'  => 'POST',
            'callback' => array($this, 'set_access_token')

        ) );
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
     * Logs in user to a site.
     */
    public function redirect_non_logged_in_users()
    {
        if(isset($_GET['access_token']))
        {
            $access_token = $_GET['access_token'];
            $user = get_users(array('meta_key' => 'access_token', 'meta_value' => $access_token))[0];

            if ($user)
            {
                wp_clear_auth_cookie();
                wp_set_current_user ( $user->ID );
                wp_set_auth_cookie  ( $user->ID );

                $redirect_to = user_admin_url();
                wp_safe_redirect( $redirect_to );
                exit();
            }
        }
    }

}