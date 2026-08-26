<?php

session_start();

require_once __DIR__ . '/../config/db.php';


// =====================================================
// SECURITY
// =====================================================

if (
    !isset($_SESSION['admin_id']) ||
    empty($_SESSION['admin_id'])
) {
    header("Location: ../login.php");
    exit;
}


$admin_id = (int) $_SESSION['admin_id'];


// =====================================================
// NO CACHE
// =====================================================

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


// =====================================================
// MESSAGE
// =====================================================

$success = "";
$error = "";


// =====================================================
// GET ADMIN
// =====================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        full_name,
        email,
        mobile,
        college_name,
        department,
        role,
        admin_code,
        profile_photo
     FROM teachers
     WHERE id = ?
     AND role = 'admin'
     LIMIT 1"
);


if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $admin_id
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


if (
    !$result ||
    mysqli_num_rows($result) === 0
) {

    $_SESSION = [];

    session_destroy();

    header("Location: ../login.php");
    exit;
}


$admin = mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


// =====================================================
// UPLOAD DIRECTORY
// =====================================================

$upload_dir =
    __DIR__ .
    '/../uploads/profile/';


if (!is_dir($upload_dir)) {

    if (!mkdir($upload_dir, 0777, true)) {

        $error =
            "Unable to create upload folder.";
    }
}


// =====================================================
// UPLOAD / CHANGE PHOTO
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['upload_photo'])
) {

    if (
        !isset($_FILES['profile_photo'])
    ) {

        $error =
            "Please select a photo.";

    } elseif (
        $_FILES['profile_photo']['error']
        !== UPLOAD_ERR_OK
    ) {

        $error =
            "Photo upload failed.";

    } else {

        $file =
            $_FILES['profile_photo'];


        // ---------------------------------------------
        // SIZE
        // ---------------------------------------------

        if (
            $file['size'] >
            2 * 1024 * 1024
        ) {

            $error =
                "Photo size must be less than 2 MB.";

        } else {

            // -----------------------------------------
            // MIME
            // -----------------------------------------

            $finfo =
                finfo_open(
                    FILEINFO_MIME_TYPE
                );


            $mime =
                finfo_file(
                    $finfo,
                    $file['tmp_name']
                );


            finfo_close($finfo);


            $allowed = [

                'image/jpeg' => 'jpg',

                'image/png' => 'png',

                'image/webp' => 'webp'

            ];


            if (
                !isset(
                    $allowed[$mime]
                )
            ) {

                $error =
                    "Only JPG, JPEG, PNG and WEBP files are allowed.";

            } else {

                // -------------------------------------
                // NEW FILE NAME
                // -------------------------------------

                $extension =
                    $allowed[$mime];


                $new_filename =
                    'admin_' .
                    $admin_id .
                    '_' .
                    bin2hex(
                        random_bytes(8)
                    ) .
                    '.' .
                    $extension;


                $destination =
                    $upload_dir .
                    $new_filename;


                // -------------------------------------
                // MOVE
                // -------------------------------------

                if (
                    move_uploaded_file(
                        $file['tmp_name'],
                        $destination
                    )
                ) {

                    // ---------------------------------
                    // DELETE OLD PHOTO
                    // ---------------------------------

                    if (
                        !empty(
                            $admin['profile_photo']
                        )
                    ) {

                        $old_photo =
                            $upload_dir .
                            basename(
                                $admin['profile_photo']
                            );


                        if (
                            is_file($old_photo)
                        ) {

                            @unlink(
                                $old_photo
                            );
                        }
                    }


                    // ---------------------------------
                    // DATABASE
                    // ---------------------------------

                    $photo_stmt =
                        mysqli_prepare(
                            $conn,

                            "UPDATE teachers
                             SET profile_photo = ?
                             WHERE id = ?
                             AND role = 'admin'"
                        );


                    if ($photo_stmt) {

                        mysqli_stmt_bind_param(
                            $photo_stmt,
                            "si",
                            $new_filename,
                            $admin_id
                        );


                        if (
                            mysqli_stmt_execute(
                                $photo_stmt
                            )
                        ) {

                            $admin['profile_photo'] =
                                $new_filename;


                            $success =
                                "Profile photo updated successfully.";

                        } else {

                            @unlink(
                                $destination
                            );


                            $error =
                                "Database update failed.";
                        }


                        mysqli_stmt_close(
                            $photo_stmt
                        );

                    } else {

                        @unlink(
                            $destination
                        );


                        $error =
                            "Unable to prepare database query.";
                    }

                } else {

                    $error =
                        "Unable to save uploaded photo.";
                }
            }
        }
    }
}


// =====================================================
// REMOVE PHOTO
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['remove_photo'])
) {

    if (
        !empty(
            $admin['profile_photo']
        )
    ) {

        $old_photo =
            $upload_dir .
            basename(
                $admin['profile_photo']
            );


        if (
            is_file($old_photo)
        ) {

            @unlink(
                $old_photo
            );
        }
    }


    $remove_stmt =
        mysqli_prepare(
            $conn,

            "UPDATE teachers
             SET profile_photo = NULL
             WHERE id = ?
             AND role = 'admin'"
        );


    if ($remove_stmt) {

        mysqli_stmt_bind_param(
            $remove_stmt,
            "i",
            $admin_id
        );


        if (
            mysqli_stmt_execute(
                $remove_stmt
            )
        ) {

            $admin['profile_photo'] =
                null;


            $success =
                "Profile photo removed.";

        } else {

            $error =
                "Unable to remove photo.";
        }


        mysqli_stmt_close(
            $remove_stmt
        );
    }
}


// =====================================================
// GENERATE ADMIN CODE
// =====================================================

if (
    empty(
        $admin['admin_code']
    )
) {

    do {

        $new_code =
            "ADMIN-" .
            strtoupper(
                substr(
                    bin2hex(
                        random_bytes(5)
                    ),
                    0,
                    8
                )
            );


        $check =
            mysqli_prepare(
                $conn,

                "SELECT id
                 FROM teachers
                 WHERE admin_code = ?
                 LIMIT 1"
            );


        mysqli_stmt_bind_param(
            $check,
            "s",
            $new_code
        );


        mysqli_stmt_execute(
            $check
        );


        $check_result =
            mysqli_stmt_get_result(
                $check
            );


        $exists =
            mysqli_num_rows(
                $check_result
            ) > 0;


        mysqli_stmt_close(
            $check
        );

    } while ($exists);


    $code_stmt =
        mysqli_prepare(
            $conn,

            "UPDATE teachers
             SET admin_code = ?
             WHERE id = ?
             AND role = 'admin'"
        );


    mysqli_stmt_bind_param(
        $code_stmt,
        "si",
        $new_code,
        $admin_id
    );


    mysqli_stmt_execute(
        $code_stmt
    );


    mysqli_stmt_close(
        $code_stmt
    );


    $admin['admin_code'] =
        $new_code;
}


// =====================================================
// UPDATE PROFILE
// =====================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_profile'])
) {

    $full_name =
        trim(
            $_POST['full_name'] ?? ''
        );


    $mobile =
        trim(
            $_POST['mobile'] ?? ''
        );


    $college_name =
        trim(
            $_POST['college_name'] ?? ''
        );


    $department =
        trim(
            $_POST['department'] ?? ''
        );


    if (
        $full_name === ''
    ) {

        $error =
            "Full name is required.";

    } elseif (
        strlen($full_name) < 2
    ) {

        $error =
            "Full name must contain at least 2 characters.";

    } elseif (
        $mobile !== '' &&
        !preg_match(
            '/^[0-9]{10}$/',
            $mobile
        )
    ) {

        $error =
            "Enter valid 10 digit mobile number.";

    } else {

        $update =
            mysqli_prepare(
                $conn,

                "UPDATE teachers
                 SET
                    full_name = ?,
                    mobile = ?,
                    college_name = ?,
                    department = ?
                 WHERE id = ?
                 AND role = 'admin'"
            );


        mysqli_stmt_bind_param(
            $update,
            "ssssi",
            $full_name,
            $mobile,
            $college_name,
            $department,
            $admin_id
        );


        if (
            mysqli_stmt_execute(
                $update
            )
        ) {

            $admin['full_name'] =
                $full_name;

            $admin['mobile'] =
                $mobile;

            $admin['college_name'] =
                $college_name;

            $admin['department'] =
                $department;


            $_SESSION['admin_name'] =
                $full_name;


            $success =
                "Profile updated successfully.";

        } else {

            $error =
                "Unable to update profile.";
        }


        mysqli_stmt_close(
            $update
        );
    }
}


// =====================================================
// ESCAPE
// =====================================================

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// =====================================================
// PHOTO URL
// =====================================================

$photo_url = "";


if (
    !empty(
        $admin['profile_photo']
    )
) {

    $photo_url =
        "../uploads/profile/" .
        rawurlencode(
            basename(
                $admin['profile_photo']
            )
        );
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

<title>Admin Profile</title>


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

    margin: 0;

    background: #f4f7fb;

    font-family: Arial, sans-serif;
}


.profile-container {

    max-width: 950px;

    margin: 40px auto;

    padding: 15px;
}


.profile-card {

    background: #fff;

    border-radius: 20px;

    overflow: hidden;

    box-shadow:
        0 8px 30px
        rgba(0,0,0,.10);
}


/* HEADER */

.profile-header {

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );

    color: white;

    text-align: center;

    padding: 45px 20px;
}


/* PHOTO */

.photo-wrapper {

    width: 125px;

    height: 125px;

    margin: auto;

    position: relative;
}


.profile-photo {

    width: 125px;

    height: 125px;

    object-fit: cover;

    border-radius: 50%;

    border: 5px solid white;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.25);
}


.default-photo {

    width: 125px;

    height: 125px;

    border-radius: 50%;

    background: white;

    color: #0d6efd;

    border: 5px solid white;

    display: flex;

    justify-content: center;

    align-items: center;

    font-size: 55px;
}


.camera {

    position: absolute;

    right: 0;

    bottom: 0;

    width: 42px;

    height: 42px;

    border-radius: 50%;

    background: white;

    color: #0d6efd;

    display: flex;

    justify-content: center;

    align-items: center;

    cursor: pointer;

    border: 2px solid #0d6efd;
}


.profile-header h2 {

    margin-top: 18px;

    font-weight: 700;
}


/* CONTENT */

.profile-content {

    padding: 35px;
}


.section-title {

    font-size: 20px;

    font-weight: 700;

    margin-bottom: 20px;
}


/* INFO */

.info-box {

    background: #f8f9fa;

    border: 1px solid #e9ecef;

    border-radius: 14px;

    padding: 18px;

    margin-bottom: 15px;
}


.info-label {

    font-size: 13px;

    color: #777;
}


.info-value {

    font-size: 16px;

    font-weight: 600;

    margin-top: 4px;

    word-break: break-word;
}


.info-icon {

    width: 40px;

    height: 40px;

    border-radius: 10px;

    background: #eaf3ff;

    color: #0d6efd;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-right: 12px;
}


/* PHOTO CARD */

.photo-card {

    margin-top: 30px;

    border: 1px solid #e5e7eb;

    border-radius: 18px;

    padding: 25px;
}


/* UNIQUE CODE */

.unique-box {

    margin-top: 30px;

    background: #eaf3ff;

    border: 2px dashed #0d6efd;

    border-radius: 18px;

    padding: 30px;

    text-align: center;
}


.unique-code {

    display: inline-block;

    margin-top: 15px;

    background: white;

    color: #0d6efd;

    font-size: 28px;

    font-weight: 800;

    letter-spacing: 3px;

    padding: 15px 25px;

    border-radius: 10px;
}


/* EDIT */

.edit-card {

    margin-top: 30px;

    border: 1px solid #e5e7eb;

    border-radius: 18px;

    padding: 25px;
}


.form-control {

    border-radius: 10px;

    padding: 11px;
}


.btn {

    border-radius: 10px;

    font-weight: 600;

    padding: 10px 18px;
}


/* MOBILE */

@media(max-width:768px) {

    .profile-content {

        padding: 20px;
    }


    .unique-code {

        width: 100%;

        font-size: 20px;

        letter-spacing: 2px;
    }

}

</style>

</head>


<body>


<div class="profile-container">

<div class="profile-card">


<!-- ==================================================
     HEADER
================================================== -->

<div class="profile-header">


<div class="photo-wrapper">


<?php if ($photo_url !== ''): ?>

<img
    src="<?= e($photo_url) ?>"
    class="profile-photo"
    id="profilePreview"
    alt="Admin Photo"
>

<?php else: ?>

<div
    class="default-photo"
    id="defaultPhoto"
>

<i class="fa-solid fa-user-shield"></i>

</div>

<?php endif; ?>


<label
    for="profile_photo"
    class="camera"
    title="Change Profile Photo"
>

<i class="fa-solid fa-camera"></i>

</label>


</div>


<h2>

<?= e(
    $admin['full_name']
) ?>

</h2>


<p class="mb-0">

<i class="fa-solid fa-shield-halved"></i>

Administrator

</p>


</div>



<!-- ==================================================
     CONTENT
================================================== -->

<div class="profile-content">


<?php if ($success): ?>

<div class="alert alert-success">

<i class="fa-solid fa-circle-check"></i>

<?= e($success) ?>

</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="alert alert-danger">

<i class="fa-solid fa-circle-exclamation"></i>

<?= e($error) ?>

</div>

<?php endif; ?>



<!-- ==================================================
     PROFILE INFORMATION
================================================== -->

<div class="section-title">

<i class="fa-solid fa-circle-info text-primary"></i>

Profile Information

</div>


<div class="row">


<div class="col-md-6">

<div class="info-box">

<div class="d-flex">

<div class="info-icon">

<i class="fa-solid fa-envelope"></i>

</div>

<div>

<div class="info-label">

Email

</div>

<div class="info-value">

<?= e(
    $admin['email']
) ?>

</div>

</div>

</div>

</div>

</div>



<div class="col-md-6">

<div class="info-box">

<div class="d-flex">

<div class="info-icon">

<i class="fa-solid fa-phone"></i>

</div>

<div>

<div class="info-label">

Mobile

</div>

<div class="info-value">

<?= e(
    $admin['mobile']
    ?: 'Not provided'
) ?>

</div>

</div>

</div>

</div>

</div>



<div class="col-md-6">

<div class="info-box">

<div class="d-flex">

<div class="info-icon">

<i class="fa-solid fa-building-columns"></i>

</div>

<div>

<div class="info-label">

College

</div>

<div class="info-value">

<?= e(
    $admin['college_name']
    ?: 'Not provided'
) ?>

</div>

</div>

</div>

</div>

</div>



<div class="col-md-6">

<div class="info-box">

<div class="d-flex">

<div class="info-icon">

<i class="fa-solid fa-graduation-cap"></i>

</div>

<div>

<div class="info-label">

Department

</div>

<div class="info-value">

<?= e(
    $admin['department']
    ?: 'Not provided'
) ?>

</div>

</div>

</div>

</div>

</div>



<div class="col-md-6">

<div class="info-box">

<div class="d-flex">

<div class="info-icon">

<i class="fa-solid fa-user-tie"></i>

</div>

<div>

<div class="info-label">

Role

</div>

<div class="info-value">

<?= e(
    ucfirst(
        $admin['role']
    )
) ?>

</div>

</div>

</div>

</div>

</div>



<div class="col-md-6">

<div class="info-box">

<div class="d-flex">

<div class="info-icon">

<i class="fa-solid fa-id-card"></i>

</div>

<div>

<div class="info-label">

Admin ID

</div>

<div class="info-value">

#<?= e(
    $admin['id']
) ?>

</div>

</div>

</div>

</div>

</div>


</div>



<!-- ==================================================
     PHOTO UPLOAD
================================================== -->

<div class="photo-card">


<h5 class="fw-bold">

<i class="fa-solid fa-camera text-primary"></i>

Profile Photo

</h5>


<form
    method="POST"
    enctype="multipart/form-data"
>


<input
    type="file"
    name="profile_photo"
    id="profile_photo"
    class="form-control mt-3"
    accept="image/jpeg,image/png,image/webp"
    required
>


<div class="form-text">

JPG, PNG or WEBP | Maximum 2 MB

</div>


<div class="mt-3">


<button
    type="submit"
    name="upload_photo"
    class="btn btn-primary"
>

<i class="fa-solid fa-upload"></i>

Upload / Change Photo

</button>


<?php if (!empty($admin['profile_photo'])): ?>

<button
    type="submit"
    name="remove_photo"
    class="btn btn-outline-danger ms-2"
    onclick="return confirm('Remove profile photo?');"
>

<i class="fa-solid fa-trash"></i>

Remove

</button>

<?php endif; ?>


</div>

</form>

</div>



<!-- ==================================================
     UNIQUE CODE
================================================== -->

<div class="unique-box">


<h5 class="fw-bold">

<i class="fa-solid fa-key text-primary"></i>

Your Admin Unique Code

</h5>


<p class="text-muted">

Give this code to a teacher when you want
them to access your classes.

</p>


<div
    class="unique-code"
    id="adminCode"
>

<?= e(
    $admin['admin_code']
) ?>

</div>


<br>


<button
    type="button"
    class="btn btn-primary mt-3"
    onclick="copyAdminCode()"
>

<i class="fa-solid fa-copy"></i>

Copy Code

</button>


</div>



<!-- ==================================================
     EDIT PROFILE
================================================== -->

<div class="edit-card">


<div class="section-title">

<i class="fa-solid fa-pen-to-square text-primary"></i>

Edit Profile

</div>


<form
    method="POST"
>


<div class="row">


<div class="col-md-6 mb-3">

<label class="form-label fw-bold">

Full Name

</label>


<input
    type="text"
    name="full_name"
    class="form-control"
    value="<?= e(
        $admin['full_name']
    ) ?>"
    required
>

</div>



<div class="col-md-6 mb-3">

<label class="form-label fw-bold">

Email

</label>


<input
    type="email"
    class="form-control"
    value="<?= e(
        $admin['email']
    ) ?>"
    disabled
>

</div>



<div class="col-md-6 mb-3">

<label class="form-label fw-bold">

Mobile

</label>


<input
    type="text"
    name="mobile"
    class="form-control"
    value="<?= e(
        $admin['mobile']
    ) ?>"
    maxlength="10"
    pattern="[0-9]{10}"
>

</div>



<div class="col-md-6 mb-3">

<label class="form-label fw-bold">

College Name

</label>


<input
    type="text"
    name="college_name"
    class="form-control"
    value="<?= e(
        $admin['college_name']
    ) ?>"
>

</div>



<div class="col-md-12 mb-3">

<label class="form-label fw-bold">

Department

</label>


<input
    type="text"
    name="department"
    class="form-control"
    value="<?= e(
        $admin['department']
    ) ?>"
>

</div>


</div>


<button
    type="submit"
    name="update_profile"
    class="btn btn-primary"
>

<i class="fa-solid fa-floppy-disk"></i>

Save Changes

</button>


</form>

</div>



<!-- ==================================================
     NAVIGATION
================================================== -->

<div class="mt-4">


<a
    href="admin_classes.php"
    class="btn btn-secondary"
>

<i class="fa-solid fa-arrow-left"></i>

Back to Classes

</a>


<a
    href="admin_logout.php"
    class="btn btn-danger ms-2"
    onclick="return confirm('Are you sure you want to logout?');"
>

<i class="fa-solid fa-right-from-bracket"></i>

Logout

</a>


</div>


</div>

</div>

</div>



<script>

// =====================================================
// PHOTO PREVIEW
// =====================================================

document
.getElementById("profile_photo")
.addEventListener(
    "change",
    function(event)
    {

        const file =
            event.target.files[0];


        if (!file) {
            return;
        }


        if (
            file.size >
            2 * 1024 * 1024
        ) {

            alert(
                "Photo must be less than 2 MB."
            );

            event.target.value = "";

            return;
        }


        const allowed = [

            "image/jpeg",

            "image/png",

            "image/webp"

        ];


        if (
            !allowed.includes(
                file.type
            )
        ) {

            alert(
                "Only JPG, PNG and WEBP allowed."
            );

            event.target.value = "";

            return;
        }


        const reader =
            new FileReader();


        reader.onload =
            function(e)
            {

                const old =
                    document.getElementById(
                        "profilePreview"
                    );


                const defaultPhoto =
                    document.getElementById(
                        "defaultPhoto"
                    );


                if (old) {

                    old.src =
                        e.target.result;

                } else if (
                    defaultPhoto
                ) {

                    const img =
                        document.createElement(
                            "img"
                        );


                    img.src =
                        e.target.result;


                    img.className =
                        "profile-photo";


                    img.id =
                        "profilePreview";


                    img.alt =
                        "Profile Photo";


                    defaultPhoto.replaceWith(
                        img
                    );
                }

            };


        reader.readAsDataURL(file);

    }
);


// =====================================================
// COPY CODE
// =====================================================

function copyAdminCode()
{

    const code =
        document
        .getElementById(
            "adminCode"
        )
        .innerText
        .trim();


    if (
        navigator.clipboard
    ) {

        navigator.clipboard
        .writeText(code)
        .then(
            function()
            {

                alert(
                    "Admin unique code copied successfully!"
                );

            }
        )
        .catch(
            function()
            {

                fallbackCopy(code);

            }
        );

    } else {

        fallbackCopy(code);

    }

}


// =====================================================
// FALLBACK COPY
// =====================================================

function fallbackCopy(text)
{

    const textarea =
        document.createElement(
            "textarea"
        );


    textarea.value = text;

    textarea.style.position =
        "fixed";

    textarea.style.left =
        "-999999px";


    document.body.appendChild(
        textarea
    );


    textarea.focus();

    textarea.select();


    try {

        document.execCommand(
            "copy"
        );


        alert(
            "Admin unique code copied successfully!"
        );

    } catch (e) {

        alert(
            "Please copy the code manually."
        );
    }


    document.body.removeChild(
        textarea
    );

}

</script>


</body>

</html>