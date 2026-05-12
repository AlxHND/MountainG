<?php

$directory = 'storage';
$pattern = $directory . '/parsed-*.txt';
$files = glob($pattern);

require('resources/views/index.template.php');
