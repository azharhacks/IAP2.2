
<?php
require '../Config.php';
require '../ClassAutoload.php';


class Forms {

    public function signup() {
        global $conf;

        // Connect to database
        $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Handle form submission
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $username = trim($_POST['username']);
            $email    = trim($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password

            // Insert into DB
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $password])) {
                echo "<p style='color:green;'>Signup successful! You can now sign in.</p>";
                require_once __DIR__ . '/../Global/SendMail.php';
        $ObjSendMail = new SendMail();
        $ObjSendMail->sendMail($conf, $username, $email);
            } else {
                echo "<p style='color:red;'>Error: could not sign up.</p>";
            }
            


        }
        

?>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>

            <?php $this->submit_button('Sign Up', 'signup'); ?>
            <a href='signin.php'>Already have an account? Sign In</a>
        </form>
<?php
    }

    public function signin() {
?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Email address</label>
                <input type="email" class="form-control" id="exampleInputEmail1" name="email" required>
            </div>
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Password</label>
                <input type="password" class="form-control" id="exampleInputPassword1" name="password" required>
            </div>

            <?php $this->submit_button('Sign In', 'signin'); ?>
            <a href='signup.php'>Don't have an account? Sign Up</a>
        </form>
<?php
    }

    public function submit_button($text, $name) {
        echo "<button type='submit' class='btn btn-primary' name='{$name}'>{$text}</button>";
    }
}
