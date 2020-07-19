<?php
require_once('../class.php');
if (!empty($_POST['username'])) {
    //Hidden form was filled in...
    //Suspected BOT
    //Do anything EXCEPT attempt register
    exit;
}
$rh = new configAndConnect();
if ($rh->issetCheck('THE_username') && $rh->issetCheck('THE_password') && $rh->issetCheck('THE_email')) {
    $register = new doRegisterAttempt($_POST['THE_username'], $_POST['THE_password'], $_POST['THE_email']);
    echo $register->attemptRegister();
} else {
    $rh->outputString("None of Username, Password or Email can be empty");
}