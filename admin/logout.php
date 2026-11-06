<?php
require_once __DIR__ . '/../includes/auth.php';
admin_session();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) { $_SESSION = []; session_destroy(); }
header('Location: login.php');
