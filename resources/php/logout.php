<?php
require_once("./class/User.php");
require_once("./class/Session.php");
require_once("./class/CSRF.php");
require_once("./class/Auth.php");

$user = new User();
$auth = new Auth();
$csrf = new CSRF();

error_log('Logout request started');

$auth->logout($_SESSION['user']['token']);
header('Location: ./logout.html');
exit;