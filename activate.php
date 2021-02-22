<?php
require_once('class.php');
if (isset($_GET['key'])) {
    $activate = new doRegisterAttempt('', '', '');
    $verify = $activate->verifyAccount($_GET['key']);
    ($verify) ? $verify->outputString("Account activated") : $verify->outputString("Key is invalid");
} else {//No key
    $cc = new configAndConnect();
    $cc->outputString("Key is required");
}
