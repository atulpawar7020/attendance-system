<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow">

<div class="container">

<a class="navbar-brand fw-bold" href="classes.php">
📚 Smart Attendance
</a>

<button class="navbar-toggler"
type="button"
data-bs-toggle="collapse"
data-bs-target="#menu">

<span class="navbar-toggler-icon"></span>

</button>

<div class="collapse navbar-collapse" id="menu">

<ul class="navbar-nav me-auto">

<li class="nav-item">
<a class="nav-link active" href="classes.php">
My Classes
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="attendance_sheet.php">
Attendance Sheet
</a>
</li>

</ul>

<ul class="navbar-nav">

<li class="nav-item dropdown">

<a
class="nav-link dropdown-toggle"
href="#"
role="button"
data-bs-toggle="dropdown">

<i class="bi bi-person-circle"></i>

<?php echo $_SESSION['teacher_name']; ?>

</a>

<ul class="dropdown-menu dropdown-menu-end">

<li>

<a class="dropdown-item" href="profile.php">

<i class="bi bi-person"></i>

My Profile

</a>

</li>

<li>

<a class="dropdown-item" href="change_password.php">

<i class="bi bi-key"></i>

Change Password

</a>

</li>

<li><hr class="dropdown-divider"></li>

<li>

<a class="dropdown-item text-danger" href="logout.php">

<i class="bi bi-box-arrow-right"></i>

Logout

</a>

</li>

</ul>

</li>

</ul>

</div>

</div>

</nav>