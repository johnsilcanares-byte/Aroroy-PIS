<?php
define('IS_PDF', true);
ob_start();
error_reporting(0);

require_once './config/connection.php';
require_once './pdflib/logics-builder-pdf.php';

if (!class_exists('LB_PDF')) {
    die('LB_PDF class not found!');
}

// ================= GET DATES =================
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

if (empty($from) || empty($to)) {
    die("Please select a date range.");
}

$fromTime = strtotime($from);
$toTime   = strtotime($to);

if (!$fromTime || !$toTime) {
    die("Invalid date format.");
}

$fromSql = date('Y-m-d', $fromTime);
$toSql   = date('Y-m-d', $toTime);

// ================= CUSTOM PDF =================
class PatientVisitPDF extends LB_PDF {

    // HEADER
    public function Header() {
        if (file_exists('images/aroroy_logo.png')) {
            $this->Image('images/aroroy_logo.png', 15, 8, 22);
        }

        if (file_exists('images/aroroy_by.png')) {
            $this->Image('images/aroroy_by.png', 260, 8, 22);
        }

        $this->SetY(10);

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, 'AROROY PATIENT INFORMATION SYSTEM', 0, 1, 'C');

        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, 'PATIENT VISITS REPORT', 0, 1, 'C');

        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'From ' . $_GET['from'] . ' to ' . $_GET['to'], 0, 1, 'C');

        $this->Ln(2);
        $this->Cell(0, 0, '', 'T', 1);
        $this->Ln(5);
    }

    // FOOTER (Page numbers)
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }

    // AUTO WRAP CELL (IMPORTANT)
    function Row($data, $widths) {
        $nb = 0;
        for ($i=0; $i<count($data); $i++) {
            $nb = max($nb, $this->NbLines($widths[$i], $data[$i]));
        }
        $h = 6 * $nb;

        $this->CheckPageBreak($h);

        for ($i=0; $i<count($data); $i++) {
            $w = $widths[$i];
            $x = $this->GetX();
            $y = $this->GetY();

            $this->Rect($x, $y, $w, $h);
            $this->MultiCell($w, 6, $data[$i], 0, 'L');
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h) {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage();
        }
    }

    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2*$this->cMargin) * 1000 / $this->FontSize;

        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);

        if ($nb > 0 && $s[$nb-1] == "\n") $nb--;

        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;

        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }

            if ($c == ' ') $sep = $i;
            $l += $cw[$c] ?? 0;

            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) $i++;
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }

        return $nl;
    }
}

// ================= GENERATE PDF =================
try {

    $pdf = new PatientVisitPDF('L', 'mm', 'A4', '', 'Aroroy Patient Information System');
    $pdf->AliasNbPages();
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();

    // ================= TABLE HEADER =================
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(230, 230, 230);

    $w = [12, 35, 55, 75, 40, 60];
    $header = ['#', 'Date', 'Name', 'Address', 'Contact', 'Disease'];

    foreach ($header as $i => $col) {
        $pdf->Cell($w[$i], 10, $col, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // ================= DATA =================
    $query = "SELECT p.patient_name, p.address, p.phone_number, pv.visit_date, pv.disease
              FROM patients p
              INNER JOIN patient_visits pv ON pv.patient_id = p.id
              WHERE pv.visit_date BETWEEN :f AND :t
              ORDER BY pv.visit_date ASC";

    $stmt = $con->prepare($query);
    $stmt->execute([':f' => $fromSql, ':t' => $toSql]);

    $pdf->SetFont('Arial', '', 9);
    $count = 0;

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;

        $name = iconv('UTF-8', 'windows-1252//TRANSLIT', $r['patient_name']);
        $address = iconv('UTF-8', 'windows-1252//TRANSLIT', $r['address']);
        $disease = iconv('UTF-8', 'windows-1252//TRANSLIT', $r['disease']);

        $pdf->Row([
            $count,
            $r['visit_date'],
            $name,
            $address,
            $r['phone_number'],
            $disease
        ], $w);
    }

    // ================= STATIC SIGNATURE (FIXED POSITION) =================
$pdf->SetY(-45); // fixed distance from bottom

$pdf->SetFont('Arial', '', 10);

// LEFT SIDE (NURSE)
$pdf->SetX(25);
$pdf->Cell(100, 6, 'Prepared by (Nurse):', 0, 1);

$pdf->SetX(25);
$pdf->Cell(100, 6, '_____________________________', 0, 1);

$pdf->SetX(25);
$pdf->Cell(100, 6, 'Name & Signature', 0, 1);


// RIGHT SIDE (DOCTOR)
$pdf->SetY(-45); // reset Y to align with left side

$pdf->SetX(170);
$pdf->Cell(100, 6, 'Approved by (Doctor):', 0, 1);

$pdf->SetX(170);
$pdf->Cell(100, 6, '_____________________________', 0, 1);

$pdf->SetX(170);
$pdf->Cell(100, 6, 'Name & Signature', 0, 1);
    // ================= OUTPUT =================
    ob_end_clean();
    $pdf->Output('I', 'Patient_Visits.pdf');
    exit;

} catch (Exception $e) {
    ob_end_clean();
    echo "Error generating PDF: " . $e->getMessage();
}