<?php
// generar_pdf.php
session_start(); // Iniciamos sesión para verificar si es admin o cliente externo

require('libs/fpdf/fpdf.php');
require('config/conexion.php');

// Lógica para detectar si venimos del script de envío de correo interno
if (!isset($_GET['id']) && !isset($id_para_envio)) { die("Error: No se especificó cotización."); }
$id = isset($id_para_envio) ? $id_para_envio : $_GET['id'];

try {
    // 1. CONFIGURACIÓN
    $sqlConfig = "SELECT * FROM configuracion_empresa WHERE id = 1";
    $stmtC = $pdo->query($sqlConfig);
    $config = $stmtC->fetch(PDO::FETCH_ASSOC);
    if(!$config) $config = ['nombre_empresa' => 'MAQUINADOS', 'politicas_generales' => ''];

    // 2. COTIZACIÓN (Traemos todo c.* incluyendo el token_url)
    $sql = "SELECT c.*, u.nombre_completo as vend_nombre, u.rfc as vend_rfc, 
                   u.direccion_calle as vend_calle, u.codigo_postal as vend_cp, u.ciudad as vend_ciudad
            FROM cotizaciones c
            LEFT JOIN usuarios u ON c.vendedor_id = u.id
            WHERE c.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cotizacion) die("Cotización no encontrada.");

    // --- BLOQUE DE SEGURIDAD (TOKEN) ---
    // Si NO hay sesión de usuario activa (es decir, es un cliente externo vía WhatsApp/Correo)...
    if (!isset($_SESSION['user_id'])) {
        // Verificamos: 1. Si trae token en la URL, 2. Si ese token coincide con la BD
        if (!isset($_GET['token']) || $_GET['token'] !== $cotizacion['token_url']) {
            // Si falla, mostramos error y detenemos la ejecución (Pantalla blanca con mensaje)
            die('<div style="font-family:sans-serif; text-align:center; padding:50px;">
                    <h1 style="color:#e53e3e;">ACCESO DENEGADO</h1>
                    <p>El enlace de seguridad es inválido o ha expirado.</p>
                    <p style="color:#718096; font-size:0.9em;">Solicite un nuevo enlace a su vendedor.</p>
                 </div>');
        }
    }
    // -----------------------------------

    // 3. ITEMS
    $stmtItems = $pdo->prepare("SELECT * FROM detalles_cotizacion WHERE cotizacion_id = :id");
    $stmtItems->execute([':id' => $id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { die("Error BD: " . $e->getMessage()); }

class PDF extends FPDF {
    var $widths; var $aligns;
    function Header() {
        global $cotizacion, $config;
        if(file_exists('assets/img/logo.png')) $this->Image('assets/img/logo.png', 10, 10, 48); 
        else { $this->SetFont('Arial','B',14); $this->Text(10, 20, "MAQUINADOS CARDONA"); }

        $this->SetY(10); $this->SetFont('Arial', '', 14);
        $num = ($cotizacion['consecutivo_cliente'] > 0) ? $cotizacion['consecutivo_cliente'] : $cotizacion['id'];
        $acro = !empty($cotizacion['cliente_acronimo']) ? $cotizacion['cliente_acronimo'] : 'GRAL';
        $folio = 'MB-' . str_pad($num, 3, "0", STR_PAD_LEFT) . $acro;

        $this->SetX(-75); $this->Cell(65, 8, utf8_decode('COTIZACIÓN: ' . $folio), 0, 1, 'R');
        
        $this->SetFont('Arial', '', 9);
        $meses = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
        $t = strtotime($cotizacion['fecha_emision']);
        $fecha = date('d', $t) . " de " . $meses[date('m', $t)] . " de " . date('Y', $t);
        $this->SetX(-75); $this->Cell(65, 5, utf8_decode('Celaya, Gto, a ' . $fecha), 0, 1, 'R');
        
        $this->Ln(4); $this->SetX(90); 
        $v_rfc = $cotizacion['vend_rfc'] ?: "";
        $v_nom = strtoupper($cotizacion['vend_nombre']);
        $v_cp  = $cotizacion['vend_cp'] ?: "";
        $v_dir = strtoupper($cotizacion['vend_calle'] . " " . $cotizacion['vend_ciudad']);
        
        $this->SetFont('Arial', 'B', 7); $this->Cell(10, 3.5, "RFC:", 0, 0, 'L'); 
        $this->SetFont('Arial', '', 7); $this->Cell(90, 3.5, utf8_decode($v_rfc), 0, 1, 'L');
        $this->SetX(90); $this->SetFont('Arial', 'B', 7); $this->Cell(90, 3.5, utf8_decode($v_nom), 0, 1, 'L');
        $this->SetX(90); $this->SetFont('Arial', 'B', 7); $this->Cell(10, 3.5, "CP:", 0, 0, 'L');
        $this->SetFont('Arial', '', 7); $this->Cell(90, 3.5, utf8_decode($v_cp), 0, 1, 'L');
        $this->SetX(90); $this->SetFont('Arial', '', 7); $this->MultiCell(100, 3.5, utf8_decode($v_dir), 0, 'L');
        
        $this->Ln(3); $this->SetLineWidth(0.4); $this->Line(10, $this->GetY(), 200, $this->GetY()); $this->SetLineWidth(0.2); $this->Ln(6);
    }
    function Footer() {
        global $config; $this->SetY(-55); 
        $this->SetFont('Arial', '', 6.5); $this->SetTextColor(150, 150, 150); 
        $this->MultiCell(0, 3, utf8_decode($config['politicas_generales']), 0, 'J');
        $this->SetTextColor(0); $this->SetY(-15); $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina '.$this->PageNo().'/{nb}', 0, 0, 'R');
    }
    function SetWidths($w) { $this->widths=$w; }
    function SetAligns($a) { $this->aligns=$a; }
    function Row($data, $fill=false) {
        $nb=0; for($i=0;$i<count($data);$i++) $nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
        $h=5*$nb + 2; $this->CheckPageBreak($h);
        for($i=0;$i<count($data);$i++) {
            $w=$this->widths[$i]; $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x=$this->GetX(); $y=$this->GetY();
            if($fill) $this->Rect($x,$y,$w,$h,'F'); $this->Rect($x,$y,$w,$h);
            $this->MultiCell($w,5,$data[$i],0,$a); $this->SetXY($x+$w,$y);
        } $this->Ln($h);
    }
    function CheckPageBreak($h) { if($this->GetY()+$h > 240) $this->AddPage($this->CurOrientation); }
    function NbLines($w,$txt) {
        $cw=&$this->CurrentFont['cw']; if($w==0) $w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize; $s=str_replace("\r",'',$txt);
        $nb=strlen($s); if($nb>0 and $s[$nb-1]=="\n") $nb--; $sep=-1; $i=0; $j=0; $l=0; $nl=1;
        while($i<$nb) { $c=$s[$i]; if($c=="\n") { $i++; $sep=-1; $j=$i; $l=0; $nl++; continue; }
        if($c==' ') $sep=$i; $l+=$cw[$c]; if($l>$wmax) { if($sep==-1) { if($i==$j) $i++; }
        else $i=$sep+1; $sep=-1; $j=$i; $l=0; $nl++; } else $i++; } return $nl;
    }
}

// GENERAR
$pdf = new PDF(); $pdf->AliasNbPages(); $pdf->AddPage(); $pdf->SetAutoPageBreak(false); 
$R=253; $G=185; $B=19;

// DATOS CLIENTE
$pdf->SetFont('Arial','B',9); $pdf->Cell(20, 5, "CLIENTE:", 0, 0); 
$pdf->SetFont('Arial','',9); $pdf->Cell(100, 5, utf8_decode($cotizacion['cliente_empresa']), 0, 1);
$pdf->SetFont('Arial','B',9); $pdf->Cell(20, 5, "NOMBRE:", 0, 0); 
$pdf->SetFont('Arial','',9); $pdf->Cell(100, 5, utf8_decode($cotizacion['cliente_contacto']), 0, 1);
$pdf->SetFont('Arial','B',9); $pdf->Cell(20, 5, "GMAIL:", 0, 0); 
$pdf->SetFont('Arial','',9); $pdf->Cell(100, 5, utf8_decode($cotizacion['cliente_correo']), 0, 1);
$pdf->Ln(6);

// TABLA
$pdf->SetFillColor($R, $G, $B); $pdf->SetFont('Arial','B',8);
$w = [22, 22, 90, 28, 28]; $pdf->SetWidths($w); $pdf->SetAligns(['C','C','L','C','C']);
$pdf->Cell($w[0], 8, "CANTIDAD", 1, 0, 'C', true); $pdf->Cell($w[1], 8, "UNIDAD", 1, 0, 'C', true); 
$pdf->Cell($w[2], 8, utf8_decode("DESCRIPCIÓN"), 1, 0, 'C', true); $pdf->Cell($w[3], 8, "VALOR UNITARIO", 1, 0, 'C', true); 
$pdf->Cell($w[4], 8, "TOTAL", 1, 1, 'C', true);

$pdf->SetFont('Arial','',8);
foreach ($items as $item) {
    $pdf->SetAligns(['C','C','L','R','R']); 
    $pdf->Row([
        number_format($item['cantidad'], (floor($item['cantidad']) == $item['cantidad'] ? 0 : 2)),
        utf8_decode($item['unidad_medida']), utf8_decode($item['descripcion']),
        '$ '.number_format($item['precio_unitario'], 2), '$ '.number_format($item['total_linea'], 2)
    ]);
}

// REF Y TOTALES
if(!empty($cotizacion['ref_general'])) {
    $pdf->SetFont('Arial','B',9); $h = 8; if($pdf->GetY()+$h > 240) $pdf->AddPage();
    $pdf->Cell($w[0], $h, "", 'LR'); $pdf->Cell($w[1], $h, "", 'LR');
    $pdf->SetFillColor(255, 255, 0); $pdf->Cell($w[2], $h, utf8_decode("REF " . $cotizacion['ref_general']), 1, 0, 'C', true);
    $pdf->Cell($w[3], $h, "", 'LR'); $pdf->Cell($w[4], $h, "", 'LR', 1); $pdf->Cell(array_sum($w), 0, '', 'T');
} else { $pdf->Cell(array_sum($w), 0, '', 'T'); }
$pdf->Ln(2);

if($pdf->GetY() > 220) $pdf->AddPage();
$yStart = $pdf->GetY();
$pdf->SetFont('Arial','B',8);
$pdf->Cell(130, 5, "Importe con letra: " . utf8_decode(numeroALetras($cotizacion['total'])), 0, 0, 'L');

$pdf->SetXY(134, $yStart); $pdf->SetFillColor($R, $G, $B); $pdf->SetFont('Arial','B',8);
$pdf->Cell(28, 6, "SUBTOTAL :", 1, 0, 'C', true);
$pdf->SetFillColor(245, 245, 245); $pdf->SetFont('Arial','',8); 
$pdf->Cell(28, 6, '$ '.number_format($cotizacion['subtotal'], 2), 1, 1, 'L', true);

$pdf->SetXY(134, $yStart + 6); $pdf->SetFillColor($R, $G, $B); $pdf->SetFont('Arial','B',8);
$pdf->Cell(28, 6, "IVA :", 1, 0, 'C', true);
$pdf->SetFillColor(245, 245, 245); $pdf->SetFont('Arial','',8); 
$pdf->Cell(28, 6, '$ '.number_format($cotizacion['monto_iva'], 2), 1, 1, 'L', true);

$pdf->SetXY(134, $yStart + 12); $pdf->SetFillColor($R, $G, $B); $pdf->SetFont('Arial','B',8);
$pdf->Cell(28, 6, "TOTAL :", 1, 0, 'C', true);
$pdf->SetFillColor(245, 245, 245); $pdf->SetFont('Arial','B',8); 
$pdf->Cell(28, 6, '$ '.number_format($cotizacion['total'], 2), 1, 1, 'L', true);
$pdf->Ln(12); 

// CONDICIONES
if($pdf->GetY() > 200) $pdf->AddPage(); $pdf->Ln(5);
$pdf->SetFillColor($R, $G, $B); $pdf->SetFont('Arial','B',8);
$wCond = [63, 63, 64]; 
$pdf->Cell($wCond[0], 6, "LUGAR DE ENTREGA", 1, 0, 'C', true); $pdf->Cell($wCond[1], 6, "TIEMPO DE ENTREGA", 1, 0, 'C', true); $pdf->Cell($wCond[2], 6, "CONDICIONES DE PAGO", 1, 1, 'C', true);
$pdf->SetFillColor(255, 255, 255); $pdf->SetFont('Arial','',7);
$pdf->Cell($wCond[0], 8, utf8_decode($cotizacion['lugar_entrega']), 1, 0, 'C'); $pdf->Cell($wCond[1], 8, utf8_decode($cotizacion['tiempo_entrega']), 1, 0, 'C'); $pdf->Cell($wCond[2], 8, utf8_decode($cotizacion['condiciones_pago']), 1, 1, 'C');

$pdf->Ln(8); $textoNota = "Nota: Es fundamental verificar e identificar si los números de parte corresponden correctamente a sus requerimientos. Le solicitamos amablemente que haga referencia a este número de cotización al realizar su orden de compra o pedido.";
$pdf->SetFont('Arial','I',8); $pdf->MultiCell(190, 5, utf8_decode($textoNota), 1, 'C');

// --- LOGICA DE SALIDA ---
// Si la variable $modo_envio está definida, retornamos STRING
if (isset($modo_envio) && $modo_envio === true) {
    $contenido_pdf = $pdf->Output('S'); 
} else {
    $num = ($cotizacion['consecutivo_cliente'] > 0) ? $cotizacion['consecutivo_cliente'] : $cotizacion['id'];
    $acro = !empty($cotizacion['cliente_acronimo']) ? $cotizacion['cliente_acronimo'] : 'GRAL';
    $pdf->Output('I', 'Cotizacion_MB-'.$num.$acro.'.pdf');
}

function numeroALetras($n){ $n=number_format($n,2,'.',''); $s=explode('.',$n); $e=(int)$s[0]; $d=$s[1]; if($e==0)return"CERO PESOS $d/100 M.N."; $u=['','UN ','DOS ','TRES ','CUATRO ','CINCO ','SEIS ','SIETE ','OCHO ','NUEVE ']; $dec=['','DIEZ ','VEINTE ','TREINTA ','CUARENTA ','CINCUENTA ','SESENTA ','SETENTA ','OCHENTA ','NOVENTA ']; $dv=['DIEZ ','ONCE ','DOCE ','TRECE ','CATORCE ','QUINCE ','DIECISEIS ','DIECISIETE ','DIECIOCHO ','DIECINUEVE ']; $cen=['','CIENTO ','DOSCIENTOS ','TRESCIENTOS ','CUATROCIENTOS ','QUINIENTOS ','SEISCIENTOS ','SETECIENTOS ','OCHOCIENTOS ','NOVECIENTOS ']; $conv=function($n,$u,$dec,$dv,$cen)use(&$conv){$o='';$c=floor($n/100);$n%=100; if($c>0)$o.=($c==1&&$n==0)?'CIEN ':$cen[$c].' '; if($n>=10&&$n<20)$o.=$dv[$n-10].' '; elseif($n>=20){$d=floor($n/10);$uni=$n%10;$o.=$dec[$d];if($uni>0)$o.='Y '.$u[$uni];else$o.='';} elseif($n>0)$o.=$u[$n]; return$o;}; $str=''; $m=floor($e/1000000); $rm=$e%1000000; if($m>0)$str.=($m==1)?'UN MILLON ':$conv($m,$u,$dec,$dv,$cen).'MILLONES '; $mi=floor($rm/1000); $rmi=$rm%1000; if($mi>0)$str.=($mi==1)?'MIL ':$conv($mi,$u,$dec,$dv,$cen).'MIL '; if($rmi>0)$str.=$conv($rmi,$u,$dec,$dv,$cen); return trim($str)." PESOS $d/100 M.N."; }
?>