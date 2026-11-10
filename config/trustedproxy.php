<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated IPs/CIDRs of the load balancers or reverse proxies in
    | front of the app (or "*" to trust the immediate caller). Only requests
    | from these addresses may set X-Forwarded-* headers, so the real client
    | IP is used for rate limiting. Leave unset when nothing sits in front.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
