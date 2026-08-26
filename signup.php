<?php

session_start();

include("config/db.php");
include("config/mail.php");

$message = "";


if(isset($_POST['signup']))
{

    // =====================================================
    // GET FORM DATA
    // =====================================================

    $fullname = mysqli_real_escape_string(
        $conn,
        trim($_POST['fullname'])
    );

    $email = mysqli_real_escape_string(
        $conn,
        trim($_POST['email'])
    );

    $mobile = mysqli_real_escape_string(
        $conn,
        trim($_POST['mobile'])
    );

    $college = mysqli_real_escape_string(
        $conn,
        trim($_POST['college'])
    );

    $department = mysqli_real_escape_string(
        $conn,
        trim($_POST['department'])
    );

    $designation = mysqli_real_escape_string(
        $conn,
        trim($_POST['designation'])
    );

    $role = isset($_POST['role'])
        ? $_POST['role']
        : "teacher";


    // =====================================================
    // ROLE VALIDATION
    // =====================================================

    if($role !== "teacher" && $role !== "admin")
    {

        $role = "teacher";

    }


    // =====================================================
    // PASSWORD
    // =====================================================

    $password = password_hash(
        $_POST['password'],
        PASSWORD_DEFAULT
    );


    // =====================================================
    // CHECK EMAIL
    // =====================================================

    $check = mysqli_query(
        $conn,

        "SELECT id
         FROM teachers
         WHERE email='$email'"
    );


    if(mysqli_num_rows($check) > 0)
    {

        $message = "
        <div class='alert alert-danger'>
            <i class='fa fa-circle-exclamation'></i>
            Email already registered
        </div>
        ";

    }
    else
    {

        // =================================================
        // GENERATE OTP
        // =================================================

        $otp = rand(100000,999999);


        // =================================================
        // OTP EXPIRE - 5 MINUTES
        // =================================================

        $expire = date(
            "Y-m-d H:i:s",
            strtotime("+5 minutes")
        );


        // =================================================
        // DELETE OLD OTP
        // =================================================

        mysqli_query(
            $conn,

            "DELETE FROM signup_verifications
             WHERE email='$email'"
        );


        // =================================================
        // INSERT TEMPORARY SIGNUP DATA
        // =================================================

        $insert = mysqli_query(
            $conn,

            "INSERT INTO signup_verifications

            (
                full_name,
                email,
                mobile,
                college_name,
                department,
                designation,
                password,
                otp,
                expires_at,
                role
            )

            VALUES

            (
                '$fullname',
                '$email',
                '$mobile',
                '$college',
                '$department',
                '$designation',
                '$password',
                '$otp',
                '$expire',
                '$role'
            )"
        );


        if($insert)
        {

            // =============================================
            // SEND OTP
            // =============================================

            if(sendOTP($email,$otp))
            {

                $_SESSION['signup_email'] = $email;

                header(
                    "Location: verify_signup_otp.php"
                );

                exit();

            }
            else
            {

                $message = "
                <div class='alert alert-danger'>
                    OTP send failed
                </div>
                ";

            }

        }
        else
        {

            $message = "
            <div class='alert alert-danger'>
                Database Error :
                ".htmlspecialchars(mysqli_error($conn))."
            </div>
            ";

        }

    }

}

?>

<!DOCTYPE html>

<html>

<head>

<title>Create Account</title>

<meta
name="viewport"
content="width=device-width, initial-scale=1"
>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet"
>

<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
rel="stylesheet"
>


<style>

body{

    background:#f5f7fb;

}


.card{

    border:none;

    border-radius:15px;

    box-shadow:
    0 5px 15px rgba(0,0,0,.1);

}


.card-header{

    background:#0d6efd;

    color:white;

    border-radius:
    15px 15px 0 0 !important;

}


.form-control{

    height:45px;

    border-radius:8px;

}


.form-label{

    font-weight:600;

}


.btn{

    height:45px;

    border-radius:8px;

    font-size:16px;

}


/* ROLE */

.role-box{

    display:flex;

    gap:15px;

}


.role-option{

    flex:1;

    border:2px solid #ddd;

    border-radius:10px;

    padding:15px;

    text-align:center;

    cursor:pointer;

    transition:.2s;

}


.role-option:hover{

    border-color:#0d6efd;

}


.role-option input{

    display:none;

}


.role-option.active{

    border-color:#0d6efd;

    background:#eef5ff;

}


.role-icon{

    font-size:25px;

    margin-bottom:5px;

    color:#0d6efd;

}


</style>

</head>


<body>


<div class="container mt-5">


<div class="row justify-content-center">


<div class="col-md-6">


<div class="card">


<div class="card-header">

<h4>

<i class="fa fa-user-plus"></i>

Create Account

</h4>

</div>


<div class="card-body">


<?php echo $message; ?>


<form method="POST">


<!-- ================================================= -->
<!-- ACCOUNT TYPE -->
<!-- ================================================= -->

<div class="mb-4">


<label class="form-label">

Account Type

</label>


<div class="role-box">


<label
class="role-option active"
id="teacherOption"
>


<input
type="radio"
name="role"
value="teacher"
checked
>


<div class="role-icon">

<i class="fa fa-chalkboard-user"></i>

</div>


<strong>

Teacher

</strong>


<div>

Teacher Account

</div>


</label>


<label
class="role-option"
id="adminOption"
>


<input
type="radio"
name="role"
value="admin"
>


<div class="role-icon">

<i class="fa fa-user-shield"></i>

</div>


<strong>

Admin

</strong>


<div>

Admin Account

</div>


</label>


</div>


</div>


<!-- FULL NAME -->

<div class="mb-3">

<label class="form-label">

Full Name

</label>


<input
type="text"
name="fullname"
class="form-control"
placeholder="Enter Full Name"
required
>

</div>


<!-- EMAIL -->

<div class="mb-3">

<label class="form-label">

Email

</label>


<input
type="email"
name="email"
class="form-control"
placeholder="Enter Email"
required
>

</div>


<!-- MOBILE -->

<div class="mb-3">

<label class="form-label">

Mobile

</label>


<input
type="text"
name="mobile"
class="form-control"
placeholder="Enter Mobile Number"
>

</div>


<!-- COLLEGE -->

<div class="mb-3">

<label class="form-label">

College

</label>


<input
type="text"
name="college"
class="form-control"
placeholder="Enter College Name"
>

</div>


<!-- DEPARTMENT -->

<div class="mb-3">

<label class="form-label">

Department

</label>


<input
type="text"
name="department"
class="form-control"
placeholder="Enter Department"
>

</div>


<!-- DESIGNATION -->

<div class="mb-3">

<label class="form-label">

Designation

</label>


<input
type="text"
name="designation"
class="form-control"
placeholder="Teacher / Professor / Admin"
>

</div>


<!-- PASSWORD -->

<div class="mb-3">

<label class="form-label">

Password

</label>


<input
type="password"
name="password"
class="form-control"
placeholder="Create Password"
required
>

</div>


<button
type="submit"
name="signup"
class="btn btn-primary w-100"
>

<i class="fa fa-user-plus"></i>

Create Account

</button>


<a
href="login.php"
class="btn btn-secondary w-100 mt-3"
>

<i class="fa fa-arrow-left"></i>

Back to Login

</a>


</form>


</div>


</div>


</div>


</div>


</div>


<script>


const teacherOption =
document.getElementById("teacherOption");

const adminOption =
document.getElementById("adminOption");


teacherOption.addEventListener(
"click",
function(){

    teacherOption.classList.add("active");

    adminOption.classList.remove("active");

});


adminOption.addEventListener(
"click",
function(){

    adminOption.classList.add("active");

    teacherOption.classList.remove("active");

});


</script>


</body>

</html>