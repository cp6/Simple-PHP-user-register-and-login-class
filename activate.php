<?php
require_once('class.php');
if (isset($_GET['key'])) {
    $activate = new doRegisterAttempt('', '', '');
    $verify = $activate->verifyAccount($_GET['key']);
    ($verify) ? $activate->outputString("Account activated") : $activate->outputString("Key is invalid");
} else {//No key
    $cc = new configAndConnect();
    $cc->outputString("Key is required");
}
