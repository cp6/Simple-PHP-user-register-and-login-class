<?php
require_once('class.php');
if (isset($_GET['key'])) {
    $activate = new doRegisterAttempt('', '', '');
    $verify = $activate->verifyAccount($_GET['key']);
    if ($verify) {
        echo "Account activated";
    } else {
        echo "Key is invalid";
    }
} else {//No key
    echo "Key is required";
}