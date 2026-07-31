<?php

// ---- IMPORTANT: catch output buffering from the very first line ----
// Agar kahin bhi PHP warning/notice print ho jaye (e.g. missing student,
// undefined index, DB error), wo text Excel binary ke andar mix ho jaata hai
// aur file "corrupted" / "cannot open" error deti hai. Isliye:
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');   // errors ko screen par mat dikhao
ini_set('log_errors', '1');       // lekin log zaroor karo (debugging ke liye)

require 'vendor/autoload.php';
include("config/db.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// ---- Input validation ----
if (!isset($_GET['class_id']) || !ctype_digit((string)$_GET['class_id'])) {
    ob_end_clean();
    die("Class ID Missing or Invalid");
}

$class_id = (int)$_GET['class_id'];
$month = isset($_GET['month']) && ctype_digit((string)$_GET['month']) ? (int)$_GET['month'] : "";
$year  = isset($_GET['year']) && ctype_digit((string)$_GET['year'])  ? (int)$_GET['year']  : "";

// ---- Fetch class (prepared statement, avoids SQL injection) ----
$stmt = mysqli_prepare($conn, "SELECT * FROM classes WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $class_id);
mysqli_stmt_execute($stmt);
$classResult = mysqli_stmt_get_result($stmt);
$class = mysqli_fetch_assoc($classResult);

if (!$class) {
    ob_end_clean();
    die("Class not found");
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Attendance");

$row = 3;

// ---- Distinct attendance dates ----
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
while ($d = mysqli_fetch_assoc($dateQuery)) {
    $dates[] = $d['attendance_date'];
}

$col = 1;
$headers = ["Roll", "Name"];

foreach ($dates as $d) {
    $headers[] = date("d-M", strtotime($d));
}

$headers[] = "Present";
$headers[] = "Absent";
$headers[] = "Percentage";

foreach ($headers as $h) {
    $sheet->setCellValue([$col, $row], $h);
    $col++;
}

$lastColIndex = count($headers);
$lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex);

// ---- Title (row 1) — plain "Attendance Sheet", class name NOT repeated here ----
$titleText = "Attendance Sheet";
if ($month !== "" && $year !== "") {
    $titleText .= " - " . date("F Y", mktime(0, 0, 0, $month, 1, $year));
}
$sheet->setCellValue("A1", $titleText);
$sheet->mergeCells("A1:{$lastColLetter}1");
$sheet->getStyle("A1")->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('FFFFFF');
$sheet->getStyle("A1")->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setRGB('2F5496');
$sheet->getStyle("A1")->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension(1)->setRowHeight(28);

// ---- Header row styling ----
$headerRange = "A{$row}:{$lastColLetter}{$row}";
$sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$sheet->getStyle($headerRange)->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setRGB('4472C4');
$sheet->getStyle($headerRange)->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle($headerRange)->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('999999');

// Freeze header + roll/name columns so they stay visible while scrolling
$sheet->freezePane("C" . ($row + 1));

$row++;

// ---- Students ----
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE class_id = ? ORDER BY roll_no ASC");
mysqli_stmt_bind_param($stmt, "i", $class_id);
mysqli_stmt_execute($stmt);
$students = mysqli_stmt_get_result($stmt);

// Preload attendance for this class in ONE query instead of one query per
// student per date (old code fired hundreds of tiny queries — slow, and
// each one is another place a warning/error could leak into the output).
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

    $attendanceMap = [];
    while ($a = mysqli_fetch_assoc($attResult)) {
        $attendanceMap[$a['student_id']][$a['attendance_date']] = $a['status'];
    }
} else {
    $attendanceMap = [];
}

while ($student = mysqli_fetch_assoc($students)) {

    $col = 1;
    $sheet->setCellValue([$col++, $row], $student['roll_no']);
    $sheet->setCellValue([$col++, $row], $student['full_name']);

    $present = 0;
    $absent = 0;

    foreach ($dates as $date) {
        $status = $attendanceMap[$student['id']][$date] ?? null;
        $cellCoord = [$col, $row];
        $cellRef = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;

        if ($status === "Present") {
            $present++;
            $value = "P";
            $sheet->setCellValue($cellCoord, $value);
            $sheet->getStyle($cellRef)->getFont()->setBold(true)->getColor()->setRGB('006100');
            $sheet->getStyle($cellRef)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C6EFCE');
        } elseif ($status !== null) {
            $absent++;
            $value = "A";
            $sheet->setCellValue($cellCoord, $value);
            $sheet->getStyle($cellRef)->getFont()->setBold(true)->getColor()->setRGB('9C0006');
            $sheet->getStyle($cellRef)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFC7CE');
        } else {
            $value = "-";
            $sheet->setCellValue($cellCoord, $value);
        }

        $sheet->getStyle($cellRef)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $col++;
    }

    $total = $present + $absent;
    $percentage = $total ? round(($present / $total) * 100) : 0;

    $sheet->setCellValue([$col++, $row], $present);
    $sheet->setCellValue([$col++, $row], $absent);
    $sheet->setCellValue([$col++, $row], $percentage . "%");

    // Zebra striping for readability
    $dataRange = "A{$row}:{$lastColLetter}{$row}";
    if ($row % 2 === 0) {
        $sheet->getStyle($dataRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');
    }
    $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D9D9D9');
    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $row++;
}

// ---- Auto size columns (correctly handles 26+ columns e.g. AA, AB...) ----
for ($i = 1; $i <= $lastColIndex; $i++) {
    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
}

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
$file .= ".xlsx";

// ---- Clear ANY buffered output (warnings, whitespace, BOM, etc.) ----
// Ye hi step hai jo "excel corrupt / cannot open" error ko fix karta hai:
// humne ob_start() top par shuru kiya tha, ab jo bhi accidentally print hua
// use yahin discard kar dete hain, headers ke bilkul fresh state se jaate hain.
if (ob_get_level()) {
    ob_end_clean();
}

// ---- Excel headers ----
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: max-age=0, must-revalidate');
header('Pragma: public');

// ---- Create writer and stream file ----
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;