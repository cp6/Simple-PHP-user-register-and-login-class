<?php
require_once('../class.php');
$session = new sessionManage();
$logged_in = $session->checkIsLoggedIn(false);//If not logged in dont redirect
if ($logged_in) {//Is logged in (has session)
    if ($session->logout()) {//Successfully killed session
        echo "Logged out";//Now logged out (no session)
    } else {
        echo "Error trying to logout";//Still logged in (has session)
    }
} else {
    echo "Not logged in to begin with";//Never had a login session
}