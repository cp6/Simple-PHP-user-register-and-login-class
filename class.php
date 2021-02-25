<?php

/*
 * Config values and database connection
 */

class configAndConnect
{
    //Website full domain URL
    const URL = 'http://127.0.0.1/Simple-PHP-user-register-and-login-class/';

    //Website name
    const WEBSITE_NAME = '';

    //Failed attempts before account temp locked
    const FAIL_ATTEMPTS_ALLOWED = 4;
    //Minutes to lock account for
    const ACCOUNT_LOCK_MINUTES = 10;

    //Min username length
    const MIN_USERNAME_LENGTH = 3;
    //Max username length
    const MAX_USERNAME_LENGTH = 24;

    //Min password length
    const MIN_PASSWORD_LENGTH = 8;
    //Max password length
    const MAX_PASSWORD_LENGTH = 124;

    //Accounts must be activated from email
    const REQUIRE_EMAIL_ACTIVATION = false;

    //Settings for email activation sender
    const EMAIL_ADDRESS = '';
    const EMAIL_HOST = '';
    const SMTP_PORT = '';
    const SMTP_USERNAME = '';
    const SMTP_PASSWORD = '';

    //MySQL server connection details
    const DB_HOSTNAME = '127.0.0.1';
    const DB_NAME = 'auth';
    const DB_USERNAME = 'root';
    const DB_PASSWORD = '';

    public function db_connect(): object
    {
        $options = array(PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
        return new PDO("mysql:host=" . self::DB_HOSTNAME . ";dbname=" . self::DB_NAME . ";charset=utf8mb4", self::DB_USERNAME, self::DB_PASSWORD, $options);
    }

    public function issetCheck(string $value): bool
    {//Makes isset check on POST's shorter
        if (isset($_POST['' . $value . ''])) {
            return true;
        } else {
            return false;
        }
    }

    public function outputString(string $string)
    {//Glorified echo
        echo $string;
    }

}

/*
 * Handle registering, detail sanitization and activation email sending
 */

class doRegisterAttempt extends configAndConnect
{
    private string $username;
    private string $stated_password;
    private string $email;
    private int $uid;
    private string $key;
    protected PDO $db;

    public function __construct(string $username, string $password, string $email)
    {
        $this->username = $username;
        $this->stated_password = $password;
        $this->email = $email;
        $this->db = (new configAndConnect)->db_connect();
    }

    public function attemptRegister(): string
    {
        if ($this->validateUsername() == 1 && $this->validatePassword() && $this->validatePassword()) {
            $this->insertAccount();
            if (configAndConnect::REQUIRE_EMAIL_ACTIVATION) {
                $this->generateActivateKey();
                $this->sendVerifyEmail();
                return "Account registered, please check for activation email";
            } else {
                $this->manualActivateAccount();
                return "Account registered and activated";
            }
        } elseif ($this->validateUsername() == 2) {
            return "Username already exists, please choose another one";
        } elseif ($this->validateUsername() == 3) {
            return "{$this->validateUsername()} Username must be between 3 and 24 characters in length";
        }
    }

    protected function validateUsername(): int
    {
        if (strlen($this->username) >= configAndConnect::MIN_USERNAME_LENGTH && strlen($this->username) <= configAndConnect::MAX_USERNAME_LENGTH) {
            $select = $this->db->prepare("SELECT `uid` FROM `users` WHERE `username` = ? LIMIT 1;");
            $select->execute([$this->username]);
            if ($select->rowCount() > 0) {//Row found for username
                //Username already exists
                return 2;
            } else {
                //Can use username
                return 1;
            }
        } else {
            return 3;
        }
    }

    protected function validatePassword(): bool
    {
        if (strlen($this->stated_password) >= configAndConnect::MIN_PASSWORD_LENGTH && strlen($this->stated_password) <= configAndConnect::MAX_PASSWORD_LENGTH) {
            return true;//Password suits min and max length
        } else {
            return false;
        }
    }

    protected function validateEmail(int $max_length = 60): bool
    {
        if (filter_var($this->email, FILTER_VALIDATE_EMAIL) && strlen($this->email) <= $max_length) {
            return true;//Valid email address
        } else {
            return false;
        }
    }

    protected function insertAccount(): void
    {
        $hashed_password = password_hash($this->stated_password, PASSWORD_DEFAULT);//Hash the submitted password
        $insert = $this->db->prepare("INSERT IGNORE INTO `users` (`username`, `password`, `email`) VALUES (?,?,?)");
        $insert->execute([$this->username, $hashed_password, $this->email]);//Create the user you defined in the form
        $this->uid = $this->db->lastInsertId();
    }

    protected function manualActivateAccount(): void
    {
        $update = $this->db->prepare("UPDATE `users` SET `activated` = 1 WHERE `uid` = ? LIMIT 1;");
        $update->execute([$this->uid]);
    }

    protected function generateActivateKey(): void
    {
        $this->key = substr(md5(rand()), 0, 24);
        $insert_key = $this->db->prepare("INSERT IGNORE INTO `activate_keys` (`key`, `uid`) VALUES (?, ?)");
        $insert_key->execute([$this->key, $this->uid]);
    }

    protected function sendVerifyEmail(): void
    {
        require('PHPMailer/PHPMailer.php');
        require('PHPMailer/Exception.php');
        require('PHPMailer/SMTP.php');
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = configAndConnect::EMAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'ssl';
        $mail->Port = configAndConnect::SMTP_PORT;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $mail->Username = configAndConnect::SMTP_USERNAME;
        $mail->Password = configAndConnect::SMTP_PASSWORD;
        $mail->setFrom(configAndConnect::EMAIL_ADDRESS, configAndConnect::WEBSITE_NAME);
        $mail->addAddress($this->email, 'New Account');
        $mail->isHTML(true);
        $mail->Subject = 'Account activation';
        $mail->Body = "<a href='" . configAndConnect::URL . "activate.php?key={$this->key}'>Click here to activate<a>";
        $mail->AltBody = "" . configAndConnect::URL . "activate.php?key={$this->key} URL to activate";
        $mail->send();
    }

    public function verifyAccount(string $key): bool
    {
        $select = $this->db->prepare("SELECT `uid` FROM `activate_keys` WHERE `key` = ? LIMIT 1;");
        $select->execute([$key]);
        if ($select->rowCount() > 0) {//Row found for key
            $result = $select->fetch();
            $update = $this->db->prepare("UPDATE `users` SET `activated` = 1 WHERE `uid` = ? LIMIT 1;");
            $update->execute([$result['uid']]);
            $delete = $this->db->prepare("DELETE FROM `activate_keys` WHERE `key` = ? LIMIT 1;");
            $delete->execute([$key]);
            return true;//Account activated
        } else {
            return false;//Key is invalid
        }
    }

}

/*
 * Handle login attempts and authentication
 */

class doLoginAttempt extends configAndConnect
{
    private string $username;
    private string $stated_password;
    private string $real_password;
    private string $ip_address;
    public int $uid;
    protected PDO $db;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->stated_password = $password;
        $this->ip_address = $_SERVER['REMOTE_ADDR'];
        $this->db = (new configAndConnect)->db_connect();
    }

    protected function getUserDataForUsername(): bool
    {
        $select = $this->db->prepare("SELECT `uid`, `username`, `password` FROM `users` WHERE `username` = ? LIMIT 1;");
        $select->execute([$this->username]);
        if ($select->rowCount() > 0) {//Row found for username
            $result = $select->fetch();
            $this->uid = $result['uid'];
            $this->real_password = $result['password'];
            return true;
        } else {//Username not found
            return false;
        }
    }

    protected function checkPasswordCorrect(): bool
    {
        if (password_verify($this->stated_password, $this->real_password)) {
            return true;//Password is correct
        } else {
            return false;//Bad password
        }
    }

    protected function doLoginWasSuccess(): void
    {
        $update = $this->db->prepare("UPDATE `users` SET `login_count` = (login_count + 1), `last_login_at` = NOW(), `last_login_ip` = ? WHERE `uid` = ? LIMIT 1;");
        $update->execute([$this->ip_address, $this->uid]);
    }

    protected function addLoginFailCount(): void
    {
        $update = $this->db->prepare("UPDATE `users` SET login_fails = (login_fails + 1), `last_fail` = NOW() WHERE `username` = ? LIMIT 1;");
        $update->execute([$this->username]);
    }

    protected function addLoginFailAttempt(): void
    {
        $insert = $this->db->prepare('INSERT IGNORE INTO `login_attempts` (`username`, `ip`) VALUES (?, ?)');
        $insert->execute([$this->username, $this->ip_address]);
    }

    protected function getRecentFailCount(): int
    {
        $select = $this->db->prepare("SELECT COUNT(*) as the_count FROM `login_attempts` WHERE `ip` = ? AND `datetime` > (NOW() - INTERVAL 10 MINUTE);");
        $select->execute([$this->ip_address]);
        return $select->fetch()['the_count'];//login fails for IP in last 10 minutes
    }

    protected function isAccountLocked(): bool
    {
        $select = $this->db->prepare("SELECT `ip` FROM `account_locks` WHERE `ip` = ? LIMIT 1;");
        $select->execute([$this->ip_address]);
        $row = $select->fetch(PDO::FETCH_ASSOC);
        if (!empty($row)) {//Row found
            return true;
        } else {//NO row found
            return false;
        }
    }

    protected function hasLockTimePassed(): bool
    {
        $select = $this->db->prepare("SELECT `locked_until` FROM `account_locks` WHERE `ip` = ? LIMIT 1;");
        $select->execute([$this->ip_address]);
        $locked_until = $select->fetchColumn();
        $locked_until_formatted = DateTime::createFromFormat('Y-m-d H:i:s', $locked_until);
        if (new DateTime() > $locked_until_formatted) {//Time has passed
            $this->removeIpLock();
            return true;
        } else {
            return false;
        }
    }

    protected function removeIpLock(): void
    {
        $delete = $this->db->prepare("DELETE FROM `account_locks` WHERE `ip` = ? LIMIT 1;");
        $delete->execute([$this->ip_address]);
    }

    protected function addIpLock(): void
    {
        $time = new DateTime();
        $time->add(new DateInterval("PT" . configAndConnect::ACCOUNT_LOCK_MINUTES . "M"));
        $time_until = $time->format("Y-m-d H:i:s");
        $insert = $this->db->prepare('INSERT IGNORE INTO `account_locks` (`ip`, `locked_until`) VALUES (?, ?)');
        $insert->execute([$this->ip_address, $time_until]);
    }

    public function attemptLogin(string $redirect_to = '')
    {
        if ($this->getRecentFailCount() >= configAndConnect::FAIL_ATTEMPTS_ALLOWED) {//IP has had X or more fails in last 10 mins
            $this->addIpLock();
            return "IP Address has been locked for " . configAndConnect::ACCOUNT_LOCK_MINUTES . " minutes";//Best to not state a lock has occurred
        }
        if ($this->getUserDataForUsername()) {//Username found
            if ($this->isAccountLocked()) {//Account locked
                if ($this->hasLockTimePassed()) {
                    if ($this->checkPasswordCorrect()) {//Password is correct
                        $this->doLoginWasSuccess();
                        session_start();
                        $_SESSION['user'] = $this->uid;//Set session as uid
                        header("Location: $redirect_to");
                        exit;
                    } else {//Password is wrong
                        $this->addLoginFailCount();//Add 1 onto login fail count
                        $this->addLoginFailAttempt();//ip and datetime into login attempt fail logs
                        return "Failed login";//Be vague in error response
                    }
                } else {
                    return "Failed login";//Still locked
                }
            } else {//Account not locked
                if ($this->checkPasswordCorrect()) {//Password is correct
                    $this->doLoginWasSuccess();
                    session_start();
                    $_SESSION['user'] = $this->uid;//Set session as uid
                    header("Location: $redirect_to");
                    exit;
                } else {//Password is wrong
                    $this->addLoginFailCount();//Add 1 onto login fail count
                    $this->addLoginFailAttempt();//ip and datetime into login attempt fail logs
                    //return "Password is wrong for {$this->username}";//Dont use this, helps brute forcing.
                    return "Failed login";//Be vague in error response
                }
            }
        } else {
            //return "Username: {$this->username} not found in DB";
            return "Failed login";//Be vague in error response
        }
    }
}

/*
 * Handles sessions: 'visitor is logged in', logout
 */

class sessionManage extends configAndConnect
{
    public int $uid;

    public function sessionStartIfNone()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();//No session stated... so start one
        }
    }

    public function checkIsLoggedIn(bool $redirect = true, string $redirect_to = "" . configAndConnect::URL . "login/"): bool
    {
        $this->sessionStartIfNone();//Start session if none already started
        if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
            $this->uid = $_SESSION['user'];
            return true;//Logged in
        } else {
            if ($redirect) {//Not logged in and do a redirect
                header("Location: $redirect_to");
                exit;
            }
            return false;
        }
    }

    public function redirectIfLoggedIn(string $redirect_to = "" . configAndConnect::URL . "account/")
    {
        $this->sessionStartIfNone();//Start session if none already started
        if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
            $this->uid = $_SESSION['user'];
            header("Location: $redirect_to");
            exit;
        }
    }

    public function logout(bool $redirect = false, string $redirect_to = ''): bool
    {
        $this->sessionStartIfNone();
        if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {//Logged in
            session_destroy();
            unset($_SESSION['user']);
            $db = (new configAndConnect)->db_connect();
            $update = $db->prepare("UPDATE `users` SET `logged_out` = NOW() WHERE `uid` = ? LIMIT 1;");
            $update->execute([$this->uid]);
            if ($redirect) {//Redirect after logout
                header("Location: $redirect_to");
                exit;
            } else {
                return true;
            }
        } else {//Was not logged in to begin with
            return false;
        }
    }


    public function isAccountActivated(): bool
    {
        $db = (new configAndConnect)->db_connect();
        $select = $db->prepare("SELECT `activated` FROM `users` WHERE `uid` = ? LIMIT 1;");
        $select->execute([$this->uid]);
        if ($select->fetch()['activated']) {
            return true;//Yes
        } else {
            return false;//No
        }
    }

}

/*
 * Details and data for logged in account
 */

class accountDetails extends configAndConnect
{
    public function accountData(): array
    {
        $db = (new configAndConnect)->db_connect();
        $select = $db->prepare("SELECT `uid`, `username`, `created`, `login_count`, `login_fails`, `last_fail`, `email` FROM `users` WHERE `uid` = ? LIMIT 1;");
        $select->execute([$_SESSION['user']]);
        return $select->fetchAll(PDO::FETCH_ASSOC)[0];
    }
}

class htmlStructure extends configAndConnect
{
    public function loginForm(): void
    {
        ?>
        <form method="post" action="login_handle.php">
            <label for="THE_username">Your username:</label>
            <input class="username" id="username" name="username" minlength="3" maxlength="24" type="text">
            <input type="text" minlength="<?php echo configAndConnect::MIN_USERNAME_LENGTH; ?>"
                   maxlength="<?php echo configAndConnect::MAX_USERNAME_LENGTH; ?>" aria-label="THE_username"
                   class="form-control"
                   name="THE_username"
                   id="THE_username"
                   aria-describedby="THE_username"
                   placeholder="Your Username" required>
            <label for="THE_password">Your password:</label>
            <input type="password" minlength="<?php echo configAndConnect::MIN_PASSWORD_LENGTH; ?>"
                   maxlength="<?php echo configAndConnect::MAX_PASSWORD_LENGTH; ?>" aria-label="password"
                   class="form-control"
                   name="THE_password"
                   id="THE_password"
                   placeholder="Password" required>
            <button type="submit" value="submit" class="btn">Login</button>
        </form>
        <?php
    }

    public function registerForm(): void
    {
        ?>
        <form method="post" action="register_handle.php">
            <label for="THE_username">Create username:</label>
            <input class="username" id="username" name="username"
                   minlength="<?php echo configAndConnect::MIN_USERNAME_LENGTH; ?>"
                   maxlength="<?php echo configAndConnect::MAX_USERNAME_LENGTH; ?>" type="text">
            <input type="text" minlength="<?php echo configAndConnect::MIN_USERNAME_LENGTH; ?>"
                   maxlength="<?php echo configAndConnect::MAX_USERNAME_LENGTH; ?>" aria-label="username"
                   class="form-control"
                   name="THE_username"
                   id="THE_username"
                   aria-describedby="username"
                   placeholder="Create Username" required>
            <label for="THE_email">Your Email:</label>
            <input type="email" minlength="6" maxlength="60" aria-label="email" class="form-control" name="THE_email"
                   id="THE_email"
                   aria-describedby="email"
                   placeholder="Your email" required>
            <label for="THE_password">Create password:</label>
            <input type="password" minlength="<?php echo configAndConnect::MIN_PASSWORD_LENGTH; ?>"
                   maxlength="<?php echo configAndConnect::MAX_PASSWORD_LENGTH; ?>" aria-label="password"
                   class="form-control"
                   name="THE_password"
                   id="THE_password" placeholder="Password" required>
            <button type="submit" value="submit" class="btn">Create</button>
        </form>
        <?php
    }

}
