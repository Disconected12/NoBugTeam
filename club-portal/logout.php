<?php
define('CLUBHUB_INIT', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

club_logout();
redirect('club-portal/login.php');