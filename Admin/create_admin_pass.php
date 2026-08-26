<?php

require_once __DIR__ . "/../config/db.php";

$message = "";


// Default admin
$email = "admin@gmail.com";
$password = "admin123";


// Check existing
$stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM admins WHERE email = ? LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $email
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


if (mysqli_num_rows($result) > 0) {

    $message = "Admin account already exists.";

} else {

    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO admins (email, password)
         VALUES (?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $email,
        $hashedPassword
    );


    if (mysqli_stmt_execute($stmt)) {

        $message = "Admin account created successfully.";

    } else {

        $message = "Error creating admin.";

    }

}

?>

<!DOCTYPE html>

<html>

<head>

<title>Create Admin</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet"
>

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow mx-auto" style="max-width:500px;">

<div class="card-body p-4">

<h3 class="mb-4">
Create Admin Account
</h3>

<div class="alert alert-info">

<?php echo htmlspecialchars($message); ?>

</div>

<p>
<strong>Email:</strong>
admin@gmail.com
</p>

<p>
<strong>Password:</strong>
admin123
</p>

<a
href="admin_login.php"
class="btn btn-primary"
>
Go to Admin Login
</a>

</div>

</div>

</div>

</body>

</html>