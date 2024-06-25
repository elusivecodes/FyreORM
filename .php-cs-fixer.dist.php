<?php
declare(strict_types=1);

use Fyre\PhpCsFixer\Config;

$config = new Config();

$config->getFinder()->in(__DIR__);

return $config;
