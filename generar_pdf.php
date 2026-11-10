<?php
/**
 * generar_pdf.php
 * Generación de Reporte en PDF de la Historia Clínica utilizando la librería FPDF.
 * Parámetro requerido por GET: paciente_id
 * Sistema: Clínica Odontológica
 */

// 1. Conexión a la base de datos y sesión segura
require_once __DIR__ . '/conexion.php';

// Validar que el usuario tenga sesión activa
if (!isset($_SESSION['usuario_id'])) {
    die("<h3>Acceso no autorizado</h3><p>Debes iniciar sesión para consultar y exportar reportes médicos.</p>");
}

// 2. Validar parámetro GET paciente_id
$pacienteId = isset($_GET['paciente_id']) ? (int)$_GET['paciente_id'] : 0;
if ($pacienteId <= 0) {
    die("<h3>Error</h3><p>Identificador de paciente no válido o no proporcionado.</p>");
}

// 3. Consultar datos del paciente en la base de datos
$stmtPaciente = $pdo->prepare("
    SELECT id, documento, nombre, apellido, telefono, email, fecha_nacimiento, created_at
    FROM pacientes
    WHERE id = ?
    LIMIT 1
");
$stmtPaciente->execute([$pacienteId]);
$paciente = $stmtPaciente->fetch();

if (!$paciente) {
    die("<h3>Paciente no encontrado</h3><p>No existe ningún paciente registrado con el ID proporcionado.</p>");
}

// 4. Consultar atenciones registradas en su historia clínica
$stmtHistoria = $pdo->prepare("
    SELECT 
        h.id,
        h.fecha_procedimiento,
        h.observaciones,
        h.costo_aplicado,
        p.nombre AS procedimiento_nombre
    FROM historias_clinicas h
    INNER JOIN procedimientos p ON h.procedimiento_id = p.id
    WHERE h.paciente_id = ?
    ORDER BY h.fecha_procedimiento ASC, h.id ASC
");
$stmtHistoria->execute([$pacienteId]);
$atenciones = $stmtHistoria->fetchAll();

// 5. Incluir la librería FPDF
require_once __DIR__ . '/fpdf/fpdf.php';

// Función auxiliar para convertir caracteres UTF-8 a ISO-8859-1 (compatibilidad FPDF)
function toIso($str) {
    return mb_convert_encoding($str ?? '', 'ISO-8859-1', 'UTF-8');
}

/**
 * Clase personalizada extendiendo FPDF para la Historia Clínica
 */
class PDF_HistoriaClinica extends FPDF {
    public $paciente;
    public $nombreOdontologo;
    public $widths;
    public $aligns;

    // Encabezado institucional
    function Header() {
        // Franja azul institucional superior
        $this->SetFillColor(13, 110, 253); // Azul Bootstrap #0d6efd
        $this->Rect(0, 0, 210, 8, 'F');

        // Título de la Clínica
        $this->SetY(14);
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(30, 41, 59);
        $this->Cell(120, 7, toIso('CLÍNICA ODONTOLÓGICA DENTALCARE PRO'), 0, 0, 'L');

        // Fecha de emisión
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(70, 7, toIso('Fecha de Emisión: ' . date('d/m/Y H:i')), 0, 1, 'R');

        // Subtítulo institucional
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(120, 5, toIso('Especialistas en Odontología Integral & Estética Dental'), 0, 0, 'L');
        $this->Cell(70, 5, toIso('Doc. Oficial N° HC-' . str_pad($this->paciente['id'], 5, '0', STR_PAD_LEFT)), 0, 1, 'R');

        // Línea divisoria
        $this->SetDrawColor(203, 213, 225);
        $this->SetLineWidth(0.5);
        $this->Line(10, 28, 200, 28);

        $this->Ln(6);
    }

    // Pie de página con espacio para la firma del odontólogo tratante
    function Footer() {
        // Posicionarse a 38 mm del borde inferior
        $this->SetY(-38);

        // Línea de firma centrada para el odontólogo tratante
        $this->SetDrawColor(71, 85, 105);
        $this->SetLineWidth(0.3);
        $this->Line(70, $this->GetY(), 140, $this->GetY());

        $this->SetY(-36);
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(30, 41, 59);
        $this->Cell(0, 4, toIso($this->nombreOdontologo), 0, 1, 'C');

        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 4, toIso('Odontólogo Tratante - Firma y Sello Profesional'), 0, 1, 'C');

        // Número de página y nota de confidencialidad
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(148, 163, 184);
        $this->Cell(130, 5, toIso('Documento médico confidencial para uso clínico legal.'), 0, 0, 'L');
        $this->Cell(60, 5, toIso('Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
    }

    // Métodos para tabla con celdas multilínea automáticas
    function SetWidths($w) {
        $this->widths = $w;
    }

    function SetAligns($a) {
        $this->aligns = $a;
    }

    function Row($data, $fill = false, $lineHeight = 6) {
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        $h = $lineHeight * $nb;

        $this->CheckPageBreak($h);

        for ($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();

            if ($fill) {
                $this->Rect($x, $y, $w, $h, 'F');
            }
            $this->Rect($x, $y, $w, $h);
            $this->MultiCell($w, $lineHeight, $data[$i], 0, $a);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h) {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n") $nb--;
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
            $l += $cw[$c];
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

// 6. Crear instancia del documento PDF
$pdf = new PDF_HistoriaClinica('P', 'mm', 'A4');
$pdf->paciente = $paciente;
$pdf->nombreOdontologo = $_SESSION['usuario_nombre'] ?? 'Dr. Especialista Odontólogo';
$pdf->AliasNbPages();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 42); // Margen inferior para preservar el pie de firma
$pdf->AddPage();

// ----------------------------------------------------
// TÍTULO DEL DOCUMENTO Y DATOS PERSONALES DEL PACIENTE
// ----------------------------------------------------
$pdf->SetY(32);
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetTextColor(13, 110, 253);
$pdf->Cell(0, 6, toIso('HISTORIA CLÍNICA INDIVIDUAL'), 0, 1, 'L');
$pdf->Ln(2);

// Recuadro de información personal del paciente
$pdf->SetFillColor(248, 250, 252); // Fondo gris muy claro
$pdf->SetDrawColor(203, 213, 225);
$pdf->SetLineWidth(0.3);
$pdf->Rect(10, $pdf->GetY(), 190, 28, 'DF');

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(71, 85, 105);

// Fila 1: Nombre y Documento
$pdf->Cell(25, 7, toIso('Paciente:'), 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(85, 7, toIso($paciente['nombre'] . ' ' . $paciente['apellido']), 0, 0, 'L');

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(71, 85, 105);
$pdf->Cell(30, 7, toIso('Documento / ID:'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(50, 7, toIso($paciente['documento']), 0, 1, 'L');

// Fila 2: Teléfono y Correo Electrónico
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(71, 85, 105);
$pdf->Cell(25, 7, toIso('Teléfono:'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(85, 7, toIso($paciente['telefono'] ?: 'No registrado'), 0, 0, 'L');

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(71, 85, 105);
$pdf->Cell(30, 7, toIso('Correo:'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(50, 7, toIso($paciente['email'] ?: 'No registrado'), 0, 1, 'L');

// Fila 3: Fecha de Nacimiento y Edad
$edadTexto = 'No especificada';
if (!empty($paciente['fecha_nacimiento'])) {
    $fechaNac = date('d/m/Y', strtotime($paciente['fecha_nacimiento']));
    $cumple = new DateTime($paciente['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($cumple)->y;
    $edadTexto = $fechaNac . " ({$edad} años)";
}

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(71, 85, 105);
$pdf->Cell(35, 7, toIso('Fecha de Nacimiento:'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(75, 7, toIso($edadTexto), 0, 0, 'L');

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(71, 85, 105);
$pdf->Cell(30, 7, toIso('Fecha Ingreso:'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(15, 23, 42);
$fechaReg = !empty($paciente['created_at']) ? date('d/m/Y', strtotime($paciente['created_at'])) : '-';
$pdf->Cell(50, 7, toIso($fechaReg), 0, 1, 'L');

$pdf->Ln(7);

// ----------------------------------------------------
// TABLA DETALLADA CON LAS ATENCIONES REGISTRADAS
// ----------------------------------------------------
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(0, 6, toIso('REGISTRO CRONOLÓGICO DE ATENCIONES ODONTOLÓGICAS'), 0, 1, 'L');
$pdf->Ln(2);

// Anchos de columnas (Total = 190 mm)
// Fecha: 35mm | Procedimiento: 45mm | Observaciones: 85mm | Costo: 25mm
$pdf->SetWidths([35, 45, 85, 25]);
$pdf->SetAligns(['C', 'L', 'L', 'R']);

// Cabecera de la tabla
$pdf->SetFillColor(30, 58, 138); // Azul institucional oscuro #1e3a8a
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetDrawColor(203, 213, 225);

$pdf->Cell(35, 8, toIso('Fecha / Hora'), 1, 0, 'C', true);
$pdf->Cell(45, 8, toIso('Procedimiento'), 1, 0, 'L', true);
$pdf->Cell(85, 8, toIso('Observaciones Médicas / Evolución'), 1, 0, 'L', true);
$pdf->Cell(25, 8, toIso('Costo ($)'), 1, 1, 'R', true);

// Filas con datos
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(30, 41, 59);

$totalInvertido = 0.00;

if (!empty($atenciones)) {
    $filaAlterna = false;
    foreach ($atenciones as $at) {
        $pdf->SetFillColor($filaAlterna ? 248 : 255, $filaAlterna ? 250 : 255, $filaAlterna ? 252 : 255);

        $fechaFmt = date('d/m/Y H:i', strtotime($at['fecha_procedimiento']));
        $procNombre = toIso($at['procedimiento_nombre']);
        $obs = toIso($at['observaciones'] ? $at['observaciones'] : 'Sin observaciones registradas.');
        $costoFmt = '$' . number_format($at['costo_aplicado'], 2);

        $totalInvertido += (float)$at['costo_aplicado'];

        $pdf->Row([$fechaFmt, $procNombre, $obs, $costoFmt], true, 5);
        $filaAlterna = !$filaAlterna;
    }

    // Fila de Totales
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(165, 8, toIso('TOTAL GENERAL ACUMULADO:'), 1, 0, 'R', true);
    $pdf->SetTextColor(22, 101, 52); // Verde elegante
    $pdf->Cell(25, 8, '$' . number_format($totalInvertido, 2), 1, 1, 'R', true);

} else {
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(190, 12, toIso('No se han registrado atenciones odontológicas previas para este paciente.'), 1, 1, 'C');
}

// 7. Generar y emitir el PDF hacia el navegador
$nombreArchivo = 'Historia_Clinica_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $paciente['documento']) . '.pdf';
$pdf->Output('I', $nombreArchivo);
exit;
