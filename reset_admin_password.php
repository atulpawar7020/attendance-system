<?php

include("config/db.php");


// Admin Email

$email = "admin@gmail.com";


// New Password

$new_password = "admin123";


// Create Hash

$hash = password_hash($new_password, PASSWORD_DEFAULT);



// Update Password

$sql = mysqli_query($conn,

"UPDATE teachers 
SET password='$hash'
WHERE email='$email'
AND role='admin'");



if($sql){


    echo "

    <h2>Admin Password Reset Successfully</h2>

    Email : admin@gmail.com <br>

    New Password : admin123

    ";


}
else{


    echo "Error : ".mysqli_error($conn);


}



?>