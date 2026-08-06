<?php

session_start();

include("config/db.php");

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;


if(!isset($_SESSION['teacher_id'])){

    header("Location: login.php");
    exit();

}


if(!isset($_GET['class_id'])){

    die("Class ID Missing");

}


$class_id = intval($_GET['class_id']);


// Get Class Details

$classQuery = mysqli_query($conn,

"SELECT * FROM classes WHERE id='$class_id'"

);


$class = mysqli_fetch_assoc($classQuery);


if(!$class){

    die("Class Not Found");

}



if(isset($_POST['upload'])){


    $file = $_FILES['excel']['tmp_name'];


    if($file==""){

        echo "<script>alert('Please select Excel file');</script>";

    }
    else{


        try{


            $spreadsheet = IOFactory::load($file);


            $sheet = $spreadsheet->getActiveSheet();


            $rows = $sheet->toArray();



            $success = 0;

            $duplicate = 0;




            foreach($rows as $row){



                // First two columns check

                $firstColumn = strtolower(trim($row[0] ?? ''));

                $secondColumn = strtolower(trim($row[1] ?? ''));



                // Skip Header

                if(

                    $firstColumn=="roll no" ||

                    $firstColumn=="roll_no" ||

                    $firstColumn=="roll number" ||

                    $secondColumn=="student name" ||

                    $secondColumn=="name"

                ){

                    continue;

                }





                // Roll Number

                $roll_no = "";

                if(isset($row[0])){

                    $roll_no = trim($row[0]);

                }




                // Student Name

                $full_name = "";

                if(isset($row[1])){

                    $full_name = trim($row[1]);

                }




                // Mobile Optional

                $mobile = "";

                if(isset($row[2])){

                    $mobile = trim($row[2]);

                }




                // Email Optional

                $email = "";

                if(isset($row[3])){

                    $email = trim($row[3]);

                }




                // Validate

                if(

                    $roll_no=="" ||

                    $full_name=="" ||

                    !is_numeric($roll_no) ||

                    $roll_no<=0

                ){

                    continue;

                }




                $roll_no = mysqli_real_escape_string($conn,$roll_no);

                $full_name = mysqli_real_escape_string($conn,$full_name);

                $mobile = mysqli_real_escape_string($conn,$mobile);

                $email = mysqli_real_escape_string($conn,$email);





                // Duplicate Check

                $check = mysqli_query($conn,


                "SELECT id FROM students

                WHERE class_id='$class_id'

                AND roll_no='$roll_no'"

                );




                if(mysqli_num_rows($check)>0){


                    $duplicate++;

                    continue;


                }




                $class_name = mysqli_real_escape_string(
                    $conn,
                    $class['class_name']
                );


                $subject = mysqli_real_escape_string(
                    $conn,
                    $class['subject']
                );





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

                    $success++;

                }



            }




            echo "

            <script>

            alert(
            'Students Added: $success'
            );

            window.location='open-class.php?class_id=$class_id';

            </script>

            ";


            exit();



        }
        catch(Exception $e){


            echo "

            <script>

            alert('Excel Error: ".$e->getMessage()."');

            </script>

            ";


        }


    }


}


?>



<!DOCTYPE html>

<html>

<head>

<title>Upload Students Excel</title>


<meta name="viewport" content="width=device-width, initial-scale=1">


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


<style>

body{

background:#f4f7fc;

}


.card{

border:none;
border-radius:20px;
box-shadow:0 5px 15px rgba(0,0,0,.15);

}


</style>


</head>


<body>


<div class="container mt-5">


<div class="row justify-content-center">


<div class="col-md-6">



<div class="card">



<div class="card-header bg-primary text-white">

<h3>
📂 Upload Students Excel
</h3>


<p>
Class:
<b>
<?php echo $class['class_name']; ?>
</b>
</p>


</div>




<div class="card-body">



<div class="alert alert-info">

<b>Excel Rules:</b>

<br>

✔ Roll No and Student Name required

<br>

✔ Mobile and Email optional

<br>

✔ Extra columns ignored

</div>




<form method="POST" enctype="multipart/form-data">


<input type="file"

name="excel"

class="form-control"

accept=".xlsx,.xls"

required>


<br>


<button type="submit"

name="upload"

class="btn btn-success w-100">


Upload Students


</button>



<br><br>


<a href="add_student.php?class_id=<?php echo $class_id; ?>"

class="btn btn-secondary w-100">

Back

</a>


</form>



</div>


</div>


</div>


</div>


</div>


</body>


</html>