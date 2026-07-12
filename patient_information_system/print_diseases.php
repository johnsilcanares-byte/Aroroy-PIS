<?php
define('IS_PDF', true);
ob_start();
error_reporting(0);

require_once './config/connection.php';
require_once './pdflib/logics-builder-pdf.php';

if (!class_exists('LB_PDF')) {
    die('LB_PDF class not found!');
}

// ---------------------- GET PARAMETERS ----------------------
$from    = $_GET['from']    ?? '';
$to      = $_GET['to']      ?? '';
$disease = $_GET['disease'] ?? '';

if (empty($from) || empty($to) || empty($disease)) {
    die("Missing parameters. Please select date range and disease.");
}

$fromTime = strtotime($from);
$toTime   = strtotime($to);
if (!$fromTime || !$toTime) {
    die("Invalid date format. Use MM/DD/YYYY.");
}

$fromSql     = date('Y-m-d', $fromTime);
$toSql       = date('Y-m-d', $toTime);
$fromDisplay = date('m/d/Y', $fromTime);
$toDisplay   = date('m/d/Y', $toTime);

// ---------------------- CUSTOM PDF CLASS ----------------------
class DiseaseReportPDF extends LB_PDF {
    protected $fromDisplay;
    protected $toDisplay;
    protected $disease;

    public function __construct($orientation, $unit, $size, $title, $author,
                                $fromDisplay, $toDisplay, $disease) {
        parent::__construct($orientation, $unit, $size, $title, $author);
        $this->fromDisplay = $fromDisplay;
        $this->toDisplay   = $toDisplay;
        $this->disease     = $disease;
    }

    public function Header() {
        // Logo left
        if (file_exists('images/aroroy_logo.png')) {
            $this->Image('images/aroroy_logo.png', 15, 8, 22);
        }
        // Logo right
        if (file_exists('images/aroroy_by.png')) {
            $this->Image('images/aroroy_by.png', 260, 8, 22);
        }

        $this->SetY(10);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, 'AROROY PATIENT INFORMATION SYSTEM', 0, 1, 'C');

        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, 'DISEASE REPORT', 0, 1, 'C');

        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, "From {$this->fromDisplay} to {$this->toDisplay}  |  Disease: "
                         . strtoupper($this->disease), 0, 1, 'C');
        $this->Ln(2);
        $this->Cell(0, 0, '', 'T', 1);
        $this->Ln(5);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Text-wrapping row with borders
    function Row($data, $widths) {
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($widths[$i], $data[$i]));
        }
        $h = 6 * $nb;
        $this->CheckPageBreak($h);
        for ($i = 0; $i < count($data); $i++) {
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
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") $nb--;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if ($c == ' ') $sep = $i;
            $l += $cw[$c] ?? 0;
            if ($l > $wmax) {
                if ($sep == -1) { if ($i == $j) $i++; }
                else { $i = $sep + 1; }
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else { $i++; }
        }
        return $nl;
    }
}

// ---------------------- GENERATE PDF ----------------------
try {
    $pdf = new DiseaseReportPDF('L', 'mm', 'A4', '',
                                'Aroroy Patient Information System',
                                $fromDisplay, $toDisplay, $disease);
    $pdf->AliasNbPages();
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();

    // 💡 Wider column widths to fill landscape page (total ~275mm)
    $w = [10, 30, 52, 72, 38, 15, 55];  // S.No, Visit Date, Name, Address, Contact#, Age, Disease
    $header = ['S.No', 'Visit Date', 'Patient Name', 'Address', 'Contact#', 'Age', 'Disease'];
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(230, 230, 230);
    foreach ($header as $i => $col) {
        $pdf->Cell($w[$i], 10, $col, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Data – includes date_of_birth for age calculation
    $query = "SELECT p.patient_name, p.address, p.phone_number, p.date_of_birth, pv.visit_date, pv.disease
              FROM patients p
              INNER JOIN patient_visits pv ON pv.patient_id = p.id
              WHERE pv.visit_date BETWEEN :f AND :t
              AND pv.disease LIKE :d
              ORDER BY pv.visit_date ASC";

    $stmt = $con->prepare($query);
    $stmt->execute([':f' => $fromSql, ':t' => $toSql, ':d' => "%$disease%"]);

    $pdf->SetFont('Arial', '', 9);
    $i = 0;
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $i++;
        $visitDate  = date('m/d/Y', strtotime($r['visit_date']));
        $name       = iconv('UTF-8', 'windows-1252//TRANSLIT', $r['patient_name']);
        $address    = iconv('UTF-8', 'windows-1252//TRANSLIT', $r['address']);
        $diseaseOut = iconv('UTF-8', 'windows-1252//TRANSLIT', $r['disease']);

        // Calculate age
        $age = 'N/A';
        if (!empty($r['date_of_birth']) && $r['date_of_birth'] !== '0000-00-00') {
            $dob = new DateTime($r['date_of_birth']);
            $now = new DateTime();
            $age = $dob->diff($now)->y . ' yrs';
        }

        $pdf->Row([$i, $visitDate, $name, $address, $r['phone_number'], $age, $diseaseOut], $w);
    }

    // ---------------------- SIGNATURE BLOCK (side‑by‑side) ----------------------
    $pdf->SetY(-50);
    $pdf->SetFont('Arial', '', 10);

    // Left (Nurse)
    $pdf->SetX(20);
    $pdf->Cell(90, 6, 'Prepared by (Nurse):', 0, 0, 'L');
    // Right (Doctor) – use same Y, then manually position
    $pdf->SetX(180);
    $pdf->Cell(90, 6, 'Approved by (Doctor):', 0, 1, 'L');
    $pdf->Ln(1);
    // Lines
    $pdf->SetX(20);
    $pdf->Cell(90, 6, '_____________________________', 0, 0, 'L');
    $pdf->SetX(180);
    $pdf->Cell(90, 6, '_____________________________', 0, 1, 'L');
    $pdf->Ln(1);
    // Names
    $pdf->SetX(20);
    $pdf->Cell(90, 6, 'Name & Signature', 0, 0, 'L');
    $pdf->SetX(180);
    $pdf->Cell(90, 6, 'Name & Signature', 0, 1, 'L');

    ob_end_clean();
    $pdf->Output('I', 'Disease_Report.pdf');
    exit;

} catch (Exception $e) {
    ob_end_clean();
    die("Error generating PDF: " . $e->getMessage());
}