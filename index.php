<?php
require_once('class.php');
$session = new sessionManage();
if ($session->checkIsLoggedIn(false)) {
    echo "You are logged in <a href='logout/'>Logout</a>";
} else {
    echo "Not logged in <a href='login/'>Login</a>";
}