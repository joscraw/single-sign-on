<?php

/*
Plugin Name: Single Sign On
Plugin URI: https://www.giftedhire.com/
Description: Allows Single Sign On Between Multiple Wordpress Instances
Version: 1.0.0
Author: Josh Crawmer <joshcrawmer4@yahoo.com>
License: GPLv2 or later
*/

// Make sure we don't expose any info if called directly

use CRMConnector\CRMSingleSignOn\Admin\Admin;
use CRMConnector\CRMSingleSignOn\Frontend\Frontend;
use CRMConnector\CRMSingleSignOn\Support\CRMSingleSignOn;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

global $crm_single_sign_on_link;

function bootstrap()
{
    define('CRM_SINGLE_SIGN_ON_LOADED', true);
    require_once 'Classes/Support/Autoload.php';

    if(is_admin())
    {
        new Admin();
    }
    new Frontend();
}

bootstrap();
