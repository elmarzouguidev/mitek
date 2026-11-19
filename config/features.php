<?php

/*
| Navigation features and the named route that makes each one real. nav_feature('pro') is true only when
| that route is registered, so a link appears the moment its page is built and never before.
*/
return [
    'callback' => 'callback.create',
    'quote' => 'quotes.standalone',
    'pro' => 'pro.show',
];
