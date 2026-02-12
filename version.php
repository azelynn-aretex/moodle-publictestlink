<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_publictestlink';
$plugin->version   = 2026020601;
$plugin->requires  = 2022041900; 
$plugin->supported = [500, 511];   // Available as of Moodle 3.9.0 or later.
// $plugin->incompatible = [400, 404];   // Available as of Moodle 3.9.0 or later.
$plugin->component = 'local_publictestlink';
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = '0.1.1';

$plugin->dependencies = [
    'mod_forum' => 2022042100,
    'mod_data' => 2022042100
];