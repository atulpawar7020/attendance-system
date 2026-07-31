<?php

// Catch any accidental warnings/notices before they can leak into the PDF stream
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

include("config/db.php");

require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ---- Input validation ----
if (!isset($_GET['class_id']) || !ctype_digit((string)$_GET['class_id'])) {
    ob_end_clean();
    die("Class ID Missing or Invalid");
}

$class_id = (int)$_GET['class_id'];
$month = isset($_GET['month']) && ctype_digit((string)$_GET['month']) ? (int)$_GET['month'] : "";
$year  = isset($_GET['year'])  && ctype_digit((string)$_GET['year'])  ? (int)$_GET['year']  : "";

// ---- Class details (prepared statement — avoids SQL injection) ----
$stmt = mysqli_prepare($conn, "SELECT * FROM classes WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $class_id);
mysqli_stmt_execute($stmt);
$classResult = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($classResult);

if (!$class) {
    ob_end_clean();
    die("Class not found");
}

// ---- PDF setup ----
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// ---- Title text (month/year label if given) ----
$monthLabel = "";
if ($month !== "" && $year !== "") {
    $monthLabel = date("F Y", mktime(0, 0, 0, $month, 1, $year));
}

$html = "
<style>
    body { font-family: Arial, sans-serif; color: #333; }
    h2 { text-align:center; color:#2F5496; margin-bottom:4px; }
    h4 { text-align:center; color:#666; font-weight:normal; margin-top:0; }
    table { border-collapse: collapse; width:100%; margin-top:15px; }
    th {
        background-color:#4472C4;
        color:#fff;
        padding:6px;
        font-size:12px;
        text-align:center;
        border:1px solid #999;
    }
    td {
        padding:6px;
        font-size:12px;
        text-align:center;
        border:1px solid #D9D9D9;
    }
    tr:nth-child(even) td { background-color:#F2F2F2; }
    .present { color:#006100; font-weight:bold; }
    .absent  { color:#9C0006; font-weight:bold; }
    .name-col { text-align:left; }
</style>

<h2>" . htmlspecialchars($class['class_name']) . " Attendance Sheet</h2>";

if ($monthLabel !== "") {
    $html .= "<h4>Month : " . htmlspecialchars($monthLabel) . "</h4>";
}

$html .= "
<table>
<tr>
<th>Roll No</th>
<th>Name</th>";

// ---- Attendance dates ----
if ($month !== "" && $year !== "") {
    $stmt = mysqli_prepare($conn, "
        SELECT DISTINCT attendance_date
        FROM attendance
        WHERE class_id = ?
        AND MONTH(attendance_date) = ?
        AND YEAR(attendance_date) = ?
        ORDER BY attendance_date ASC
    ");
    mysqli_stmt_bind_param($stmt, "iii", $class_id, $month, $year);
} else {
    $stmt = mysqli_prepare($conn, "
        SELECT DISTINCT attendance_date
        FROM attendance
        WHERE class_id = ?
        ORDER BY attendance_date ASC
    ");
    mysqli_stmt_bind_param($stmt, "i", $class_id);
}

mysqli_stmt_execute($stmt);
$dateQuery = mysqli_stmt_get_result($stmt);

$dates = [];
while ($date = mysqli_fetch_assoc($dateQuery)) {
    $dates[] = $date['attendance_date'];
    $html .= "<th>" . date('d-m-Y', strtotime($date['attendance_date'])) . "</th>";
}

$html .= "<th>Overall %</th></tr>";

// ---- Students ----
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE class_id = ? ORDER BY roll_no ASC");
mysqli_stmt_bind_param($stmt, "i", $class_id);
mysqli_stmt_execute($stmt);
$studentQuery = mysqli_stmt_get_result($stmt);

// Preload attendance for this class in ONE query instead of one query per
// student per date — faster, and fewer places for a DB error to slip through.
$attendanceMap = [];
if (!empty($dates)) {
    $placeholders = implode(",", array_fill(0, count($dates), "?"));
    $types = "i" . str_repeat("s", count($dates));
    $params = array_merge([$class_id], $dates);

    $attStmt = mysqli_prepare($conn, "
        SELECT student_id, attendance_date, status
        FROM attendance
        WHERE class_id = ?
        AND attendance_date IN ($placeholders)
    ");
    mysqli_stmt_bind_param($attStmt, $types, ...$params);
    mysqli_stmt_execute($attStmt);
    $attResult = mysqli_stmt_get_result($attStmt);

    while ($a = mysqli_fetch_assoc($attResult)) {
        $attendanceMap[$a['student_id']][$a['attendance_date']] = $a['status'];
    }
}

while ($student = mysqli_fetch_assoc($studentQuery)) {

    $total = 0;
    $present = 0;

    $html .= "
    <tr>
    <td>" . htmlspecialchars($student['roll_no']) . "</td>
    <td class='name-col'>" . htmlspecialchars($student['full_name']) . "</td>";

    foreach ($dates as $date) {
        $total++;
        $status = $attendanceMap[$student['id']][$date] ?? null;

        if ($status === "Present") {
            $present++;
            $html .= "<td class='present'>P</td>";
        } elseif ($status !== null) {
            $html .= "<td class='absent'>A</td>";
        } else {
            $html .= "<td>-</td>";
        }
    }

    $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;

    $html .= "<td>" . $percentage . "%</td></tr>";
}

$html .= "</table>";

// ---- Generate PDF ----
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// ---- File name — based on the class being downloaded ----
$safeClassName = preg_replace('/[^A-Za-z0-9]+/', '_', trim($class['class_name']));
$safeClassName = trim($safeClassName, '_');
if ($safeClassName === '') {
    $safeClassName = 'Class';
}

$file = $safeClassName . "_Attendance";
if ($month !== "" && $year !== "") {
    $file .= "_" . date("M-Y", mktime(0, 0, 0, $month, 1, $year));
}
$file .= ".pdf";

// ---- Clear any buffered/leaked output before streaming the PDF ----
if (ob_get_level()) {
    ob_end_clean();
}

$dompdf->stream($file, ["Attachment" => true]);
exit;