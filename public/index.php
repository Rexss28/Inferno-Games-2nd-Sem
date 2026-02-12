<?php

use App\Kernel;

// Set the default timezone for the entire application
date_default_timezone_set($_SERVER['APP_TIMEZONE'] ?? 'Asia/Manila');

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};