<?php
include("config/db.php");

if(isset($_POST['class_id'])){

    $class_id = mysqli_real_escape_string($conn,$_POST['class_id']);
    $roll_no = mysqli_real_escape_string($conn,$_POST['roll_no']);
    $full_name = mysqli_real_escape_string($conn,$_POST['full_name']);
    $mobile = mysqli_real_escape_string($conn,$_POST['mobile']);
    $email = mysqli_real_escape_string($conn,$_POST['email']);

    // Get Class Details
    $classQuery = mysqli_query($conn,
    "SELECT * FROM classes WHERE id='$class_id'");

    if(mysqli_num_rows($classQuery)==0){

        die("Class Not Found");

    }

    $class = mysqli_fetch_assoc($classQuery);

    $class_name = $class['class_name'];
    $subject = $class['subject'];

    // Check Duplicate Roll Number
    $checkRoll = mysqli_query($conn,
    "SELECT id FROM students
    WHERE class_id='$class_id'
    AND roll_no='$roll_no'");

    if(mysqli_num_rows($checkRoll)>0){

        echo "
        <script>
        alert('This Roll Number is already registered in this class.');
        history.back();
        </script>
        ";

        exit();

    }

    // Check Duplicate Email
    if($email!=""){

        $checkEmail = mysqli_query($conn,
        "SELECT id FROM students
        WHERE class_id='$class_id'
        AND email='$email'");

        if(mysqli_num_rows($checkEmail)>0){

            echo "
            <script>
            alert('This Email is already registered.');
            history.back();
            </script>
            ";

            exit();

        }

    }

    // Insert Student
    $insert = mysqli_query($conn,

    "INSERT INTO students
    (
        class_id,
        roll_no,
        full_name,
        mobile,
        email,
        class_name,
        subject
    )

    VALUES
    (
        '$class_id',
        '$roll_no',
        '$full_name',
        '$mobile',
        '$email',
        '$class_name',
        '$subject'
    )"

    );

    if($insert){

        echo "
        <script>
        alert('Successfully Joined The Class');
        window.location='join_class.php?code=".$class['invite_code']."';
        </script>
        ";

    }else{

        echo mysqli_error($conn);

    }

}else{

    header("Location:index.php");

}
?>