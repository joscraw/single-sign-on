<?php

namespace CRMConnector\CRMSingleSignOn\Admin;


/**
 * Class Admin
 * @package CRMConnector\CRMSingleSignOn\Admin
 */
class Admin
{

    public function __construct()
    {

    add_action( 'admin_init', array($this, 'crmc_single_sign_on_settings'));
    add_action( 'admin_menu', array($this, 'crmc_single_sign_on_menu'));

    }

    public function crmc_single_sign_on_menu()
    {
        add_menu_page( 'Single Sign On Settings', 'Single Sign On Settings', 'single_sign_on_settings', 'single_sign_on_settings', array($this, 'crmc_single_sign_on_settings_page') );
    }

    public function crmc_single_sign_on_settings()
    {
        register_setting( 'single_sign_on_settings', 'crmc_settings' );

        add_settings_section(
            'single_sign_on_section',
            __( 'Single Sign On Settings', 'wordpress' ),
            function(){},
            'single_sign_on_settings'
        );

        add_settings_field(
            'website_urls',
            __( 'Website URL', 'wordpress' ),
            function()
            {
                $options = get_option( 'crmc_settings' );
                ?>
                <input type='text' size="100" name='crmc_settings[website_url]' value='<?php echo $options['website_url']; ?>'>
                <?php
            },
            'single_sign_on_settings',
            'single_sign_on_section'
        );
    }

    public static function crmc_single_sign_on_settings_page()
    {
        ?>
        <form action='options.php' method='post'>

            <?php
            settings_fields( 'single_sign_on_settings' );
            do_settings_sections( 'single_sign_on_settings' );
            submit_button();
            ?>

        </form>
        <?php
    }


}