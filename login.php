<?php

session_start();

require_once __DIR__ . "/config/db.php";

$error = "";


// Agar teacher already login hai
if (isset($_SESSION['teacher_id'])) {

    header("Location: classes.php");
    exit();

}


// Agar admin already login hai
if (isset($_SESSION['admin_id'])) {

    header("Location: Admin/admin_classes.php");
    exit();

}


// Login
if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? "");
    $password = $_POST['password'] ?? "";


    if ($email == "" || $password == "") {

        $error = "Please enter email and password.";

    } else {


        $email_safe = mysqli_real_escape_string($conn, $email);


        $sql = "
            SELECT *
            FROM teachers
            WHERE email = '$email_safe'
            LIMIT 1
        ";


        $result = mysqli_query($conn, $sql);


        if (!$result) {

            $error = "Database Error: " . mysqli_error($conn);

        }

        elseif (mysqli_num_rows($result) == 0) {

            $error = "Invalid Email or Password.";

        }

        else {

            $user = mysqli_fetch_assoc($result);


            // Password check
            if (!password_verify($password, $user['password'])) {

                $error = "Invalid Email or Password.";

            }

            else {

                /*
                |--------------------------------------------------------------------------
                | ADMIN
                |--------------------------------------------------------------------------
                */

                if (strtolower($user['role']) === "admin") {


                    unset($_SESSION['teacher_id']);

                    $_SESSION['admin_id'] = $user['id'];

                    $_SESSION['admin_name'] = $user['full_name'];

                    $_SESSION['admin_email'] = $user['email'];

                    $_SESSION['admin_role'] = "admin";


                    header("Location: Admin/admin_classes.php");

                    exit();

                }


                /*
                |--------------------------------------------------------------------------
                | TEACHER
                |--------------------------------------------------------------------------
                */

                else {


                    unset($_SESSION['admin_id']);

                    $_SESSION['teacher_id'] = $user['id'];

                    $_SESSION['teacher_name'] = $user['full_name'];

                    $_SESSION['teacher_email'] = $user['email'];

                    $_SESSION['teacher_role'] = "teacher";


                    header("Location: classes.php");

                    exit();

                }

            }

        }

    }

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>Login - Smart Attendance</title>


<link
rel="stylesheet"
href="assets/css/login.css"
>


<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
>


<style>

/* ================================
   LOGIN PAGE POSITION FIX
================================ */

html,
body {
    min-height: 100%;
}

body {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px 20px;
}

/* Login box ko thoda neeche */
.container {
    transform: translateY(10px) !important;
}





.password-box {

    position: relative;

}


.password-box input {

    width: 100%;

    padding-right: 45px;

}


.password-box i {

    position: absolute;

    right: 15px;

    top: 43px;

    cursor: pointer;

    color: #555;

}


.error-box {

    background: #ffe5e5;

    color: #d00000;

    padding: 10px;

    border-radius: 8px;

    text-align: center;

    margin-bottom: 15px;

    font-weight: bold;

}


</style>

</head>


<body>


<div class="container">


    <!-- LEFT -->

    <div class="left">

        <h1>🎓 Smart Attendance</h1>

        <p>

            Attendance Management System

            <br><br>

            ✓ Admin & Teacher Access

            <br>


            ✓ Easy Class Management

            <br>

            ✓ Quick Student Management

            <br>

            ✓ Fast Attendance Tracking

            <br>

            ✓ Accurate Attendance Records

            <br>

            ✓ Easy Reports

        </p>

    </div>



    <!-- RIGHT -->

    <div class="right">


        <h2>Login</h2>


        <?php if ($error != "") { ?>

            <div class="error-box">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php } ?>


        <form method="POST">


            <div class="input-group">

                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    required
                >

            </div>



            <div class="input-group password-box">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                >


                <i
                    class="fa fa-eye"
                    id="togglePassword"
                ></i>

            </div>



            <button
                type="submit"
                name="login"
                class="btn"
            >

                Login

            </button>


        </form>



        <div class="links">

            <br>

            <a href="forgot_password.php">

                Forgot Password?

            </a>


            <br><br>


            Don't have an account?


            <br>


            <a href="signup.php">

                Create Account

            </a>

        </div>


    </div>


</div>


<script>

const togglePassword =
document.querySelector("#togglePassword");

const password =
document.querySelector("#password");


togglePassword.onclick = function () {

    if (password.type === "password") {

        password.type = "text";

        this.classList.remove("fa-eye");

        this.classList.add("fa-eye-slash");

    }

    else {

        password.type = "password";

        this.classList.remove("fa-eye-slash");

        this.classList.add("fa-eye");

    }

};

</script>


</body>

</html>