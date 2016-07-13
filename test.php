<?php

use H2akim\B2\B2API;

require __DIR__ . '/vendor/autoload.php';

$b2api = new B2API('c57a3e99f485', '001b7836cccf2cc5e25acc264f98815672baf3f666');
print_r($b2api->b2_create_bucket('testing-bucket-lah2'));
