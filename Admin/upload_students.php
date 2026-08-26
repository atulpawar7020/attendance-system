<?php

session_start();

require_once __DIR__ . '/../config/db.php';

/*
|--------------------------------------------------------------------------
| PHPSPREADSHEET AUTOLOAD
|--------------------------------------------------------------------------
*/

$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php'
];

$autoloadFound = false;

foreach ($autoloadPaths as $autoload) {

    if (file_exists($autoload)) {

        require_once $autoload;

        $autoloadFound = true;

        break;
    }
}

if (!$autoloadFound) {

    die(
        "PhpSpreadsheet is not installed.<br><br>" .
        "Please run this command in your project folder:<br>" .
        "<b>composer require phpoffice/phpspreadsheet</b>"
    );
}


/*
|--------------------------------------------------------------------------
| PHPSPREADSHEET CLASSES
|--------------------------------------------------------------------------
*/

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

/**
 * Escape HTML
 */
function e($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/**
 * Normalize column name
 */
function normalizeHeader($header): string
{
    $header = trim((string)$header);

    // Lowercase
    $header = strtolower($header);

    // Replace special characters with space
    $header = preg_replace(
        '/[^a-z0-9]+/',
        ' ',
        $header
    );

    // Remove extra spaces
    $header = preg_replace(
        '/\s+/',
        ' ',
        $header
    );

    return trim($header);
}


/**
 * Detect column from aliases
 */
function findColumnIndex(
    array $headers,
    array $aliases
): ?int {

    foreach ($headers as $index => $header) {

        $normalized = normalizeHeader($header);

        foreach ($aliases as $alias) {

            if ($normalized === normalizeHeader($alias)) {

                return $index;
            }
        }
    }

    /*
     * Partial matching
     */
    foreach ($headers as $index => $header) {

        $normalized = normalizeHeader($header);

        foreach ($aliases as $alias) {

            $aliasNormalized = normalizeHeader($alias);

            if (
                $aliasNormalized !== '' &&
                (
                    strpos($normalized, $aliasNormalized) !== false ||
                    strpos($aliasNormalized, $normalized) !== false
                )
            ) {

                return $index;
            }
        }
    }

    return null;
}


/**
 * Get cell value safely
 */
function getCellValue(
    array $row,
    ?int $index
): string {

    if ($index === null) {

        return '';
    }

    if (!isset($row[$index])) {

        return '';
    }

    return trim((string)$row[$index]);
}


/*
|--------------------------------------------------------------------------
| ADMIN LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['admin_id']) ||
    empty($_SESSION['admin_id'])
) {

    header("Location: ../login.php");
    exit();
}

$admin_id = (int)$_SESSION['admin_id'];


/*
|--------------------------------------------------------------------------
| CLASS ID
|--------------------------------------------------------------------------
*/

$class_id = 0;


/*
 * Accept both:
 *
 * upload_students.php?class_id=5
 *
 * and
 *
 * upload_students.php?id=5
 *
 */

if (isset($_GET['class_id'])) {

    $class_id = (int)$_GET['class_id'];

} elseif (isset($_POST['class_id'])) {

    $class_id = (int)$_POST['class_id'];

} elseif (isset($_GET['id'])) {

    $class_id = (int)$_GET['id'];
}


/*
|--------------------------------------------------------------------------
| VALIDATE CLASS ID
|--------------------------------------------------------------------------
*/

if ($class_id <= 0) {

    die(
        "<div style='
            font-family:Arial;
            padding:40px;
            text-align:center;
        '>
            <h2>Invalid Class ID</h2>

            <p>
                Please open the upload page from
                <b>Admin Classes → Add Student → Upload Excel Sheet</b>.
            </p>

            <a href='admin_classes.php'>
                Back to Classes
            </a>
        </div>"
    );
}


/*
|--------------------------------------------------------------------------
| GET CLASS
|--------------------------------------------------------------------------
*/

$classStmt = mysqli_prepare(
    $conn,

    "SELECT
        id,
        class_name,
        academic_year,
        admin_id
     FROM classes
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $classStmt,
    "i",
    $class_id
);

mysqli_stmt_execute($classStmt);

$classResult = mysqli_stmt_get_result(
    $classStmt
);

$class = mysqli_fetch_assoc(
    $classResult
);

mysqli_stmt_close($classStmt);


/*
|--------------------------------------------------------------------------
| CLASS NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$class) {

    die(
        "<div style='
            font-family:Arial;
            padding:40px;
            text-align:center;
        '>
            <h2>Class Not Found</h2>

            <p>
                The selected class does not exist.
            </p>

            <a href='admin_classes.php'>
                Back to Classes
            </a>
        </div>"
    );
}


/*
|--------------------------------------------------------------------------
| CLASS OWNERSHIP CHECK
|--------------------------------------------------------------------------
*/

if ((int)$class['admin_id'] !== $admin_id) {

    die(
        "<div style='
            font-family:Arial;
            padding:40px;
            text-align:center;
        '>
            <h2>Access Denied</h2>

            <p>
                You do not have permission to upload students
                to this class.
            </p>

            <a href='admin_classes.php'>
                Back to Classes
            </a>
        </div>"
    );
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$message = '';

$messageType = '';


$successCount = 0;

$skipCount = 0;

$errorCount = 0;

$errors = [];

$detectedColumns = [];


/*
|--------------------------------------------------------------------------
| FILE UPLOAD
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['upload_students'])
) {


    /*
    |--------------------------------------------------------------------------
    | CHECK FILE
    |--------------------------------------------------------------------------
    */

    if (
        !isset($_FILES['student_file']) ||
        $_FILES['student_file']['error'] !== UPLOAD_ERR_OK
    ) {

        $message =
            "Please select a valid file.";

        $messageType = "danger";

    } else {


        $file = $_FILES['student_file'];


        /*
        |--------------------------------------------------------------------------
        | FILE SIZE
        |--------------------------------------------------------------------------
        */

        $maxSize = 10 * 1024 * 1024; // 10 MB

        if ($file['size'] > $maxSize) {

            $message =
                "File size must be less than 10 MB.";

            $messageType = "danger";

        } else {


            /*
            |--------------------------------------------------------------------------
            | FILE EXTENSION
            |--------------------------------------------------------------------------
            */

            $extension = strtolower(
                pathinfo(
                    $file['name'],
                    PATHINFO_EXTENSION
                )
            );


            /*
            |--------------------------------------------------------------------------
            | ALLOWED FILE TYPES
            |--------------------------------------------------------------------------
            */

            $allowedExtensions = [
                'xlsx',
                'xls',
                'csv',
                'ods'
            ];


            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {

                $message =
                    "Unsupported file format. " .
                    "Please upload XLSX, XLS, CSV or ODS.";

                $messageType = "danger";

            } else {


                /*
                |--------------------------------------------------------------------------
                | LOAD SPREADSHEET
                |--------------------------------------------------------------------------
                */

                try {


                    /*
                    |--------------------------------------------------------------------------
                    | CSV
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT:
                    | We DO NOT call setDelimiter()
                    | on IReader.
                    |
                    */

                    if ($extension === 'csv') {


                        /*
                        |--------------------------------------------------------------------------
                        | Detect delimiter
                        |--------------------------------------------------------------------------
                        */

                        $handle = fopen(
                            $file['tmp_name'],
                            'r'
                        );


                        if (!$handle) {

                            throw new Exception(
                                "Unable to open CSV file."
                            );
                        }


                        /*
                        | Read first line
                        */

                        $firstLine = fgets(
                            $handle
                        );


                        fclose($handle);


                        if ($firstLine === false) {

                            throw new Exception(
                                "CSV file is empty."
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Detect delimiter
                        |--------------------------------------------------------------------------
                        */

                        $delimiters = [
                            "," => substr_count(
                                $firstLine,
                                ","
                            ),

                            ";" => substr_count(
                                $firstLine,
                                ";"
                            ),

                            "\t" => substr_count(
                                $firstLine,
                                "\t"
                            ),

                            "|" => substr_count(
                                $firstLine,
                                "|"
                            )
                        ];


                        arsort($delimiters);


                        $delimiter =
                            array_key_first(
                                $delimiters
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Read CSV
                        |--------------------------------------------------------------------------
                        */

                        $handle = fopen(
                            $file['tmp_name'],
                            'r'
                        );


                        if (!$handle) {

                            throw new Exception(
                                "Unable to read CSV file."
                            );
                        }


                        $rows = [];


                        while (
                            ($data = fgetcsv(
                                $handle,
                                0,
                                $delimiter
                            )) !== false
                        ) {

                            $rows[] = $data;
                        }


                        fclose($handle);


                    } else {


                        /*
                        |--------------------------------------------------------------------------
                        | XLSX / XLS / ODS
                        |--------------------------------------------------------------------------
                        */

                        $spreadsheet =
                            IOFactory::load(
                                $file['tmp_name']
                            );


                        /*
                        | First sheet
                        */

                        $worksheet =
                            $spreadsheet
                            ->getActiveSheet();


                        /*
                        | Get all rows
                        */

                        $rows =
                            $worksheet
                            ->toArray(
                                null,
                                true,
                                true,
                                false
                            );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | CHECK EMPTY FILE
                    |--------------------------------------------------------------------------
                    */

                    if (
                        empty($rows)
                    ) {

                        throw new Exception(
                            "The uploaded file is empty."
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | FIND HEADER ROW
                    |--------------------------------------------------------------------------
                    |
                    | Header may not necessarily be
                    | first row.
                    |
                    */

                    $headerRowIndex = null;


                    $maxHeaderSearch =
                        min(
                            10,
                            count($rows)
                        );


                    for (
                        $i = 0;
                        $i < $maxHeaderSearch;
                        $i++
                    ) {


                        $testHeaders =
                            $rows[$i];


                        $normalizedHeaders = [];


                        foreach (
                            $testHeaders
                            as $header
                        ) {

                            $normalizedHeaders[] =
                                normalizeHeader(
                                    $header
                                );
                        }


                        $hasRoll = false;

                        $hasName = false;


                        foreach (
                            $normalizedHeaders
                            as $header
                        ) {


                            if (
                                in_array(
                                    $header,
                                    [
                                        'roll',
                                        'roll no',
                                        'roll number',
                                        'rollno',
                                        'rollnumber',
                                        'student roll',
                                        'student roll no',
                                        'student roll number',
                                        'sr no',
                                        'sr number',
                                        'registration number',
                                        'registration no',
                                        'reg no',
                                        'reg number'
                                    ],
                                    true
                                )
                            ) {

                                $hasRoll = true;
                            }


                            if (
                                in_array(
                                    $header,
                                    [
                                        'name',
                                        'student name',
                                        'full name',
                                        'student full name',
                                        'student',
                                        'fullname'
                                    ],
                                    true
                                )
                            ) {

                                $hasName = true;
                            }
                        }


                        if (
                            $hasRoll &&
                            $hasName
                        ) {

                            $headerRowIndex =
                                $i;

                            break;
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | HEADER NOT FOUND
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $headerRowIndex === null
                    ) {

                        throw new Exception(
                            "Required columns not found. " .
                            "The file must contain Roll Number and Name columns."
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | GET HEADERS
                    |--------------------------------------------------------------------------
                    */

                    $headers =
                        $rows[$headerRowIndex];


                    /*
                    |--------------------------------------------------------------------------
                    | DETECT COLUMNS
                    |--------------------------------------------------------------------------
                    */

                    $rollIndex =
                        findColumnIndex(
                            $headers,
                            [
                                'roll',
                                'roll no',
                                'roll number',
                                'rollno',
                                'rollnumber',
                                'student roll',
                                'student roll no',
                                'student roll number',
                                'sr no',
                                'sr number',
                                'registration number',
                                'registration no',
                                'reg no',
                                'reg number'
                            ]
                        );


                    $nameIndex =
                        findColumnIndex(
                            $headers,
                            [
                                'name',
                                'student name',
                                'full name',
                                'student full name',
                                'student',
                                'fullname'
                            ]
                        );


                    $emailIndex =
                        findColumnIndex(
                            $headers,
                            [
                                'email',
                                'email id',
                                'email address',
                                'student email',
                                'mail',
                                'e mail'
                            ]
                        );


                    $mobileIndex =
                        findColumnIndex(
                            $headers,
                            [
                                'mobile',
                                'mobile no',
                                'mobile number',
                                'contact',
                                'contact no',
                                'contact number',
                                'phone',
                                'phone no',
                                'phone number',
                                'telephone',
                                'student mobile',
                                'student contact'
                            ]
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | REQUIRED COLUMNS
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $rollIndex === null
                    ) {

                        throw new Exception(
                            "Roll Number column not found."
                        );
                    }


                    if (
                        $nameIndex === null
                    ) {

                        throw new Exception(
                            "Student Name column not found."
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | DISPLAY DETECTED COLUMNS
                    |--------------------------------------------------------------------------
                    */

                    $detectedColumns = [

                        'Roll Number' =>
                            $headers[$rollIndex],

                        'Name' =>
                            $headers[$nameIndex],

                        'Email' =>
                            $emailIndex !== null
                                ? $headers[$emailIndex]
                                : 'Not Found',

                        'Mobile' =>
                            $mobileIndex !== null
                                ? $headers[$mobileIndex]
                                : 'Not Found'
                    ];


                    /*
                    |--------------------------------------------------------------------------
                    | PREPARE DUPLICATE CHECK
                    |--------------------------------------------------------------------------
                    */

                    $duplicateStmt =
                        mysqli_prepare(
                            $conn,

                            "SELECT id
                             FROM students
                             WHERE class_id = ?
                             AND roll_no = ?
                             LIMIT 1"
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | INSERT STUDENT
                    |--------------------------------------------------------------------------
                    */

                    $insertStmt =
                        mysqli_prepare(
                            $conn,

                            "INSERT INTO students
                            (
                                class_id,
                                roll_no,
                                full_name,
                                mobile,
                                email
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                ?,
                                ?,
                                ?
                            )"
                        );


                    if (!$insertStmt) {

                        throw new Exception(
                            "Database error while preparing student insert."
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | PROCESS DATA ROWS
                    |--------------------------------------------------------------------------
                    */

                    for (
                        $rowNumber =
                            $headerRowIndex + 1;

                        $rowNumber < count($rows);

                        $rowNumber++
                    ) {


                        $row =
                            $rows[$rowNumber];


                        /*
                        | Ignore completely empty rows
                        */

                        $allEmpty = true;


                        foreach (
                            $row as $cell
                        ) {

                            if (
                                trim((string)$cell)
                                !== ''
                            ) {

                                $allEmpty = false;

                                break;
                            }
                        }


                        if ($allEmpty) {

                            continue;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | GET VALUES
                        |--------------------------------------------------------------------------
                        */

                        $roll_no =
                            getCellValue(
                                $row,
                                $rollIndex
                            );


                        $full_name =
                            getCellValue(
                                $row,
                                $nameIndex
                            );


                        $email =
                            getCellValue(
                                $row,
                                $emailIndex
                            );


                        $mobile =
                            getCellValue(
                                $row,
                                $mobileIndex
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | REQUIRED DATA
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $roll_no === '' ||
                            $full_name === ''
                        ) {

                            $errorCount++;

                            $errors[] =
                                "Row " .
                                ($rowNumber + 1) .
                                ": Roll Number and Name are required.";

                            continue;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | CLEAN ROLL NUMBER
                        |--------------------------------------------------------------------------
                        */

                        $roll_no =
                            trim(
                                $roll_no
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | CLEAN NAME
                        |--------------------------------------------------------------------------
                        */

                        $full_name =
                            trim(
                                $full_name
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | EMAIL OPTIONAL
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $email !== '' &&
                            !filter_var(
                                $email,
                                FILTER_VALIDATE_EMAIL
                            )
                        ) {

                            /*
                            | Invalid email:
                            | don't stop upload.
                            | Just make it empty.
                            */

                            $email = '';
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | MOBILE OPTIONAL
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $mobile !== ''
                        ) {

                            /*
                            | Keep only digits.
                            */

                            $mobile =
                                preg_replace(
                                    '/[^0-9]/',
                                    '',
                                    $mobile
                                );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | CHECK DUPLICATE
                        |--------------------------------------------------------------------------
                        */

                        mysqli_stmt_bind_param(
                            $duplicateStmt,
                            "is",
                            $class_id,
                            $roll_no
                        );


                        mysqli_stmt_execute(
                            $duplicateStmt
                        );


                        $duplicateResult =
                            mysqli_stmt_get_result(
                                $duplicateStmt
                            );


                        if (
                            mysqli_num_rows(
                                $duplicateResult
                            ) > 0
                        ) {

                            $skipCount++;

                            $errors[] =
                                "Row " .
                                ($rowNumber + 1) .
                                ": Roll Number " .
                                e($roll_no) .
                                " already exists. Skipped.";

                            continue;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | INSERT
                        |--------------------------------------------------------------------------
                        */

                        mysqli_stmt_bind_param(
                            $insertStmt,
                            "issss",
                            $class_id,
                            $roll_no,
                            $full_name,
                            $mobile,
                            $email
                        );


                        if (
                            mysqli_stmt_execute(
                                $insertStmt
                            )
                        ) {

                            $successCount++;

                        } else {

                            $errorCount++;

                            $errors[] =
                                "Row " .
                                ($rowNumber + 1) .
                                ": " .
                                mysqli_stmt_error(
                                    $insertStmt
                                );
                        }
                    }


                    mysqli_stmt_close(
                        $duplicateStmt
                    );


                    mysqli_stmt_close(
                        $insertStmt
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | FINAL MESSAGE
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $successCount > 0
                    ) {

                        $message =
                            $successCount .
                            " student(s) uploaded successfully.";

                        if (
                            $skipCount > 0
                        ) {

                            $message .=
                                " " .
                                $skipCount .
                                " duplicate student(s) skipped.";
                        }


                        if (
                            $errorCount > 0
                        ) {

                            $message .=
                                " " .
                                $errorCount .
                                " row(s) had errors.";
                        }


                        $messageType =
                            "success";

                    } else {

                        if (
                            $skipCount > 0
                        ) {

                            $message =
                                "No new students were added. " .
                                $skipCount .
                                " duplicate student(s) found.";

                        } else {

                            $message =
                                "No students were uploaded.";
                        }


                        $messageType =
                            "warning";
                    }


                } catch (
                    Throwable $exception
                ) {

                    $message =
                        "Upload Error: " .
                        $exception->getMessage();

                    $messageType =
                        "danger";
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

<title>
Upload Students -
<?= e($class['class_name']) ?>
</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet"
>


<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
rel="stylesheet"
>


<style>

body {

    background: #f5f7fb;

    font-family: Arial, sans-serif;

}


.header {

    background: white;

    box-shadow:
        0 2px 10px rgba(0,0,0,.08);

    padding: 20px;

}


.upload-card {

    max-width: 850px;

    margin: 40px auto;

    background: white;

    border-radius: 20px;

    box-shadow:
        0 5px 25px rgba(0,0,0,.10);

    padding: 30px;

}


.upload-area {

    border: 2px dashed #0d6efd;

    background: #f4f8ff;

    border-radius: 16px;

    padding: 45px 25px;

    text-align: center;

    transition: .2s;

}


.upload-area:hover {

    background: #eaf2ff;

}


.upload-icon {

    font-size: 60px;

    color: #0d6efd;

}


.file-input {

    max-width: 500px;

    margin: 20px auto;

}


.requirement {

    background: #f8f9fa;

    border-radius: 12px;

    padding: 20px;

}


.column-box {

    background: #f8f9fa;

    border-radius: 10px;

    padding: 12px 15px;

    margin-bottom: 10px;

}


.error-list {

    max-height: 300px;

    overflow-y: auto;

}


.btn {

    border-radius: 9px;

    font-weight: 600;

}


</style>

</head>


<body>


<!-- HEADER -->

<div class="header">

<div class="container">

<div class="d-flex justify-content-between align-items-center">


<div>

<h4 class="mb-1">

<i class="fa-solid fa-file-excel text-success"></i>

Upload Students

</h4>


<div class="text-muted">

<?= e($class['class_name']) ?>

&nbsp; | &nbsp;

<?= e($class['academic_year']) ?>

</div>

</div>


<a
href="add_student.php?class_id=<?= $class_id ?>"
class="btn btn-secondary"
>

<i class="fa-solid fa-arrow-left"></i>

Back

</a>


</div>

</div>

</div>


<!-- MAIN -->

<div class="container">


<div class="upload-card">


<h4 class="fw-bold mb-2">

<i class="fa-solid fa-upload text-primary"></i>

Upload Student File

</h4>


<p class="text-muted">

Upload your student Excel/CSV file. Column order does not matter.

</p>


<?php if ($message !== ''): ?>

<div
class="alert alert-<?= e($messageType) ?>"
>

<i class="fa-solid fa-circle-info"></i>

<?= e($message) ?>

</div>

<?php endif; ?>


<!-- UPLOAD -->

<form
method="POST"
enctype="multipart/form-data"
>


<input
type="hidden"
name="class_id"
value="<?= $class_id ?>"
>


<div class="upload-area">


<div class="upload-icon">

<i class="fa-solid fa-file-arrow-up"></i>

</div>


<h5 class="mt-3">

Select Student File

</h5>


<p class="text-muted">

Supported:

<b>
XLSX, XLS, CSV, ODS
</b>

</p>


<input
type="file"
name="student_file"
class="form-control file-input"
accept=".xlsx,.xls,.csv,.ods"
required
>


<button
type="submit"
name="upload_students"
class="btn btn-primary mt-3 px-4"
>

<i class="fa-solid fa-upload"></i>

Upload Students

</button>


</div>

</form>


<!-- REQUIREMENTS -->

<div class="requirement mt-4">


<h5 class="fw-bold">

<i class="fa-solid fa-circle-check text-success"></i>

Column Requirements

</h5>


<div class="row mt-3">


<div class="col-md-6">


<div class="column-box">

<i class="fa-solid fa-hashtag text-primary"></i>

<b>Roll Number</b>

<span class="text-danger">
Required
</span>

</div>


</div>


<div class="col-md-6">


<div class="column-box">

<i class="fa-solid fa-user text-primary"></i>

<b>Student Name</b>

<span class="text-danger">
Required
</span>

</div>


</div>


<div class="col-md-6">


<div class="column-box">

<i class="fa-solid fa-envelope text-secondary"></i>

<b>Email</b>

<span class="text-success">
Optional
</span>

</div>


</div>


<div class="col-md-6">


<div class="column-box">

<i class="fa-solid fa-phone text-secondary"></i>

<b>Mobile / Contact</b>

<span class="text-success">
Optional
</span>

</div>


</div>


<div class="col-12">


<div class="column-box">

<i class="fa-solid fa-plus text-warning"></i>

<b>Extra Columns</b>

<span class="text-success">
Automatically ignored
</span>

</div>


</div>


</div>


<hr>


<p class="mb-0 text-muted">

<strong>Example:</strong>

Your file can have columns in any order:

<code>
Name | Department | Roll No | Email | Mobile | Gender
</code>

The system will automatically detect:

<b>
Roll No, Name, Email and Mobile
</b>

and ignore other columns.

</p>


</div>


<!-- DETECTED COLUMNS -->

<?php if (!empty($detectedColumns)): ?>

<div class="mt-4">


<h5 class="fw-bold">

<i class="fa-solid fa-magnifying-glass text-primary"></i>

Detected Columns

</h5>


<div class="row mt-3">


<?php foreach (
    $detectedColumns
    as $field => $column
): ?>

<div class="col-md-6 mb-2">

<div class="column-box">


<b>
<?= e($field) ?>:
</b>


<span class="text-primary">

<?= e($column) ?>

</span>


</div>

</div>

<?php endforeach; ?>


</div>

</div>

<?php endif; ?>


<!-- ERRORS -->

<?php if (!empty($errors)): ?>

<div class="mt-4">


<h5 class="fw-bold text-danger">

<i class="fa-solid fa-triangle-exclamation"></i>

Upload Details

</h5>


<div class="alert alert-warning error-list">


<ul class="mb-0">

<?php foreach (
    $errors
    as $error
): ?>

<li>
<?= $error ?>
</li>

<?php endforeach; ?>

</ul>


</div>


</div>

<?php endif; ?>


<!-- BACK -->

<div class="mt-4">


<a
href="add_student.php?class_id=<?= $class_id ?>"
class="btn btn-secondary"
>

<i class="fa-solid fa-arrow-left"></i>

Back to Add Student

</a>


<a
href="admin_classes.php"
class="btn btn-outline-primary ms-2"
>

<i class="fa-solid fa-graduation-cap"></i>

My Classes

</a>


</div>


</div>

</div>


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>