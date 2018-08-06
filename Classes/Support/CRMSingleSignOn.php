<?php

namespace CRMConnector\CRMSingleSignOn\Support;

/**
 * Class CRMSingleSignOn
 * @package CRMConnector\CRMSingleSignOn\Support
 */
class CRMSingleSignOn
{

    /**
     * Set the Access Token on the user for the website we are being directed from
     */
    public static function set_directed_from_site_access_token()
    {
        /*$access_token = bin2hex(random_bytes(22, MCRYPT_DEV_URANDOM));
        $user = wp_get_current_user();
        add_user_meta($user->data->ID, sprintf("user_%s_access_token", $user->data->ID), $access_token, $unique = true);*/
    }

    /**
     * Set the Access Token on the user for the website we are being directed to
     */
    public static function set_directed_to_site_access_token()
    {
        $user = wp_get_current_user();
        $access_token = get_user_meta($user->data->ID, sprintf("user_%s_access_token", $user->data->ID))[0];

        $options = get_option( 'crmc_settings' );
        $website_url = $options['website_urls'];

        $website_url = 'http://www.hs.test/wp-json/mynamespace/v1/latest-post';

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
}