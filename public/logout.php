<?php
require_once __DIR__ . '/../backend/helpers/auth.php';
session_destroy();
header('Location: /public/login.php');
exit;
