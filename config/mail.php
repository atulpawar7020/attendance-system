<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';


function sendOTP($email,$otp)
{

    $mail = new PHPMailer(true);


    try
    {

        // Debug (testing ke liye)
        $mail->SMTPDebug = 0;


        // SMTP Configuration

        $mail->isSMTP();

        $mail->Host = "smtp.sendgrid.net";

        $mail->SMTPAuth = true;


        // SendGrid SMTP Username
        $mail->Username = "apikey";


        // New SendGrid API Key yaha dale
        $mail->Password = "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";


        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;



        // Charset

        $mail->CharSet = "UTF-8";



        // Sender (SendGrid Verified Sender)

        $mail->setFrom(
            "atulpawar9940@gmail.com",
            "Smart Attendance System"
        );


        $mail->addReplyTo(
            "atulpawar9940@gmail.com",
            "Smart Attendance System"
        );



        // Receiver

        $mail->addAddress($email);



        // Email Type

        $mail->isHTML(true);



        // Subject

        $mail->Subject = "Smart Attendance OTP";



        // Message

        $mail->Body = "

        <html>
        <body>

        <h2>Smart Attendance System</h2>

        <p>Hello,</p>

        <p>Your OTP is:</p>

        <h1>$otp</h1>

        <p>This OTP is valid for 5 minutes.</p>

        <p>
        If you did not request this OTP, ignore this email.
        </p>

        <br>

        <p>
        Regards,<br>
        Smart Attendance Team
        </p>

        </body>
        </html>

        ";



        $mail->AltBody =
        "Your Smart Attendance OTP is: ".$otp;



        $mail->send();


        return true;


    }
    catch(Exception $e)
    {

        echo "Mail Error: ".$mail->ErrorInfo;

        return false;

    }

}

?>