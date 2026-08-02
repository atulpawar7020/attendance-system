<?php

include("config/mail.php");


$user_email = "student@gmail.com";


$otp = rand(100000,999999);


if(sendOTP($user_email,$otp))
{
    echo "OTP sent to ".$user_email;
}
else
{
    echo "OTP Failed";
}

?>