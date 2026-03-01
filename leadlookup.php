<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Lead Lookup
Description: Internal API to lookup leads by phone and return JSON.
Version: 1.0.6
Author: Internal
*/

define('LEADLOOKUP_MODULE_NAME', 'leadlookup');

register_activation_hook(LEADLOOKUP_MODULE_NAME, 'leadlookup_activation_hook');
function leadlookup_activation_hook()
{
    // No DB tables, nothing to install.
}

hooks()->add_action('admin_init', 'leadlookup_admin_init');
function leadlookup_admin_init()
{
    // Load module config globally (no sections for simplest access)
    $CI = &get_instance();
    $CI->config->load(LEADLOOKUP_MODULE_NAME . '/leadlookup');
}
