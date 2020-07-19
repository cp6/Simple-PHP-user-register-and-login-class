<?php
require_once('class.php');
if (isset($_GET['key'])) {
    $activate = new doRegisterAttempt('', '', '');
    $verify = $activate->verifyAccount($_GET['key']);
    if ($verify) {
        $verify->outputString("Account activated");
    } else {
        $verify->outputString("Key is invalid");
    }
} else {//No key
    $verify->outputString("Key is required");
}