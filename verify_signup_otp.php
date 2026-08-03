<?php

session_start();

include("config/db.php");


$message="";


// Check session

if(!isset($_SESSION['signup_email']))
{

    header("Location: signup.php");
    exit();

}


$email=$_SESSION['signup_email'];



if(isset($_POST['verify']))
{


    $otp = trim($_POST['otp']);



    // Check OTP

    $query=mysqli_query($conn,

    "SELECT * FROM signup_verifications
     WHERE email='$email'
     AND otp='$otp'
     LIMIT 1"

    );



    if(mysqli_num_rows($query)>0)
    {


        $row=mysqli_fetch_assoc($query);



        // Check expiry manually

        if(strtotime($row['expires_at']) < time())
        {

            $message="OTP Expired";

        }
        else
        {


            // Insert teacher account

            $insert=mysqli_query($conn,

            "INSERT INTO teachers
            (
            full_name,
            email,
            mobile,
            college_name,
            department,
            designation,
            password
            )

            VALUES

            (
            '".$row['full_name']."',
            '".$row['email']."',
            '".$row['mobile']."',
            '".$row['college_name']."',
            '".$row['department']."',
            '".$row['designation']."',
            '".$row['password']."'
            )

            "

            );



            if($insert)
            {


                // Delete temporary OTP

                mysqli_query($conn,

                "DELETE FROM signup_verifications
                 WHERE email='$email'"

                );



                unset($_SESSION['signup_email']);



                echo "

                <script>

                alert('Account Created Successfully');

                window.location='login.php';

                </script>

                ";

                exit();


            }
            else
            {

                $message="Account create error: ".mysqli_error($conn);

            }


        }


    }
    else
    {

        $message="Invalid OTP";

    }



}

?>

<!DOCTYPE html>

<html>

<head>

<title>Verify Signup OTP</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

</head>


<body>


<div class="container mt-5">


<div class="card shadow p-4 mx-auto"
style="max-width:400px;">


<h3 class="text-center">

Verify OTP

</h3>



<p>

OTP sent to:

<b>

<?php echo $email; ?>

</b>

</p>



<form method="POST">


<input 
type="text"
name="otp"
class="form-control mb-3"
placeholder="Enter OTP"
required>



<button 
name="verify"
class="btn btn-success w-100">

Verify OTP

</button>


</form>



<div class="text-danger mt-3">

<?php echo $message; ?>

</div>



</div>


</div>


</body>

</html>