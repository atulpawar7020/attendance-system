<?php

session_start();

include("config/db.php");

$message = "";


// =====================================================
// CHECK SIGNUP SESSION
// =====================================================

if(!isset($_SESSION['signup_email']))
{
    header("Location: signup.php");
    exit();
}


$email = mysqli_real_escape_string(
    $conn,
    $_SESSION['signup_email']
);


// =====================================================
// VERIFY OTP
// =====================================================

if(isset($_POST['verify']))
{

    $otp = trim($_POST['otp']);


    // =================================================
    // CHECK OTP
    // =================================================

    $query = mysqli_query(
        $conn,

        "SELECT *
         FROM signup_verifications
         WHERE email='$email'
         AND otp='$otp'
         LIMIT 1"
    );


    if(!$query)
    {

        $message =
        "Database Error: "
        . mysqli_error($conn);

    }
    elseif(mysqli_num_rows($query) > 0)
    {

        $row = mysqli_fetch_assoc($query);


        // =================================================
        // CHECK OTP EXPIRY
        // =================================================

        if(strtotime($row['expires_at']) < time())
        {

            $message = "OTP Expired";

        }
        else
        {

            // =============================================
            // GET ROLE
            // =============================================

            $role = isset($row['role'])
                ? $row['role']
                : 'teacher';


            // Safety validation

            if($role !== 'admin' && $role !== 'teacher')
            {
                $role = 'teacher';
            }


            // =============================================
            // CHECK EMAIL AGAIN
            // =============================================

            $emailCheck = mysqli_query(
                $conn,

                "SELECT id
                 FROM teachers
                 WHERE email='$email'
                 LIMIT 1"
            );


            if(mysqli_num_rows($emailCheck) > 0)
            {

                $message =
                "Email already registered.";

            }
            else
            {

                // =========================================
                // INSERT ACCOUNT
                // =========================================

                $insert = mysqli_query(
                    $conn,

                    "INSERT INTO teachers

                    (
                        full_name,
                        email,
                        mobile,
                        college_name,
                        department,
                        designation,
                        password,
                        role
                    )

                    VALUES

                    (
                        '".$row['full_name']."',
                        '".$row['email']."',
                        '".$row['mobile']."',
                        '".$row['college_name']."',
                        '".$row['department']."',
                        '".$row['designation']."',
                        '".$row['password']."',
                        '$role'
                    )"
                );


                // =========================================
                // ACCOUNT CREATED
                // =========================================

                if($insert)
                {

                    // =====================================
                    // DELETE TEMPORARY OTP
                    // =====================================

                    mysqli_query(
                        $conn,

                        "DELETE FROM signup_verifications
                         WHERE email='$email'"
                    );


                    // =====================================
                    // CLEAR SESSION
                    // =====================================

                    unset(
                        $_SESSION['signup_email']
                    );


                    // =====================================
                    // SUCCESS
                    // =====================================

                    echo "

                    <!DOCTYPE html>

                    <html>

                    <head>

                    <title>Account Created</title>

                    <link
                    href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css'
                    rel='stylesheet'
                    >

                    </head>

                    <body>

                    <div class='container mt-5'>

                    <div
                    class='alert alert-success
                    text-center shadow'
                    >

                    <h4>
                    Account Created Successfully
                    </h4>

                    <p>
                    Your account has been created.
                    </p>

                    <p>
                    Redirecting to login...
                    </p>

                    </div>

                    </div>


                    <script>

                    setTimeout(function(){

                        window.location='login.php';

                    },1500);

                    </script>

                    </body>

                    </html>

                    ";

                    exit();

                }
                else
                {

                    $message =
                    "Account create error: "
                    . mysqli_error($conn);

                }

            }

        }

    }
    else
    {

        $message = "Invalid OTP";

    }

}

?>

<!DOCTYPE html>

<html>

<head>

<title>Verify Signup OTP</title>

<meta
name="viewport"
content="width=device-width, initial-scale=1"
>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
rel="stylesheet"
>


<style>

body{

    background:#f5f7fb;

}


.card{

    border:none;

    border-radius:15px;

}


.otp-input{

    text-align:center;

    font-size:24px;

    letter-spacing:8px;

    font-weight:bold;

}

</style>

</head>


<body>


<div class="container mt-5">


<div
class="card shadow p-4 mx-auto"
style="max-width:420px;"
>


<h3 class="text-center mb-3">

<i class="fa fa-shield-halved"></i>

Verify OTP

</h3>


<p class="text-center">

OTP sent to:

<br>

<b>

<?php

echo htmlspecialchars($email);

?>

</b>

</p>


<?php

if($message != "")
{

?>

<div class="alert alert-danger text-center">

<?php

echo htmlspecialchars($message);

?>

</div>

<?php

}

?>


<form method="POST">


<label class="form-label">

Enter 6 Digit OTP

</label>


<input
type="text"
name="otp"
class="form-control otp-input mb-3"
placeholder="000000"
maxlength="6"
pattern="[0-9]{6}"
inputmode="numeric"
required
>


<button
type="submit"
name="verify"
class="btn btn-success w-100"
>

<i class="fa fa-check"></i>

Verify OTP

</button>


</form>


<div class="text-center mt-3">

<a
href="signup.php"
class="text-decoration-none"
>

← Back to Signup

</a>

</div>


</div>


</div>


</body>

</html>