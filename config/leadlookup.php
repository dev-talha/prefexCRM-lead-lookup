<?php
defined('BASEPATH') or exit('No direct script access allowed');

$config['leadlookup'] = [
    // Static API key (send via query param: ?apikey=YOUR_KEY)
    'api_key' => 'YOUR_STATIC_SECRET_KEY_HERE',

    // Phone search mode:
    // - 'like'  : partial match (recommended for formatting differences)
    // - 'exact' : exact string match
    'phone_match' => 'like',
];
