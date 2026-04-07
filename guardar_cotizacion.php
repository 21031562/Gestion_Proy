<?php
// php/guardar_cotizacion.php
session_start();
// No forzamos header JSON aquí todavía para manejar errores de upload si los hay
require '../config/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Sesión expirada"]);
    exit;
}

// 1. DETECTAR TIPO DE DATOS (JSON vs FORM-DATA)
// Si viene json puro (ediciones viejas), lo decodificamos. Si viene POST (con fotos), lo usamos directo.
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (strpos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    // Viene de FormData (con fotos)
    $data = $_POST;
    // Los items vienen como string JSON dentro del POST, hay que decodificarlos
    if (isset($data['items']) && is_string($data['items'])) {
        $data['items'] = json_decode($data['items'], true);
    }
}

if (!$data) {
    echo json_encode(["success" => false, "message" => "Datos inválidos"]);
    exit;
}

try {
    $pdo->beginTransaction();
    $quoteId = null;
    
    // Limpieza básica
    $acronimo = isset($data['cliente_acronimo']) ? strtoupper(trim($data['cliente_acronimo'])) : 'GRAL';
    if(empty($acronimo)) $acronimo = 'GRAL';

    // --- A) EDICIÓN O B) INSERCIÓN ---
    if (isset($data['id']) && !empty($data['id'])) {
        // EDICIÓN
        $quoteId = $data['id'];
        
        // Verificar permisos
        if ($_SESSION['user_rol'] !== 'administrador') {
            $stmtCheck = $pdo->prepare("SELECT id FROM cotizaciones WHERE id = :id AND vendedor_id = :uid");
            $stmtCheck->execute([':id' => $quoteId, ':uid' => $_SESSION['user_id']]);
            if (!$stmtCheck->fetch()) throw new Exception("Sin permiso para editar.");
        }

        $sqlUpdate = "UPDATE cotizaciones SET 
                      cliente_empresa = :cliente, cliente_acronimo = :acro, 
                      cliente_contacto = :contacto, cliente_correo = :correo,
                      ref_general = :ref, lugar_entrega = :lugar, tiempo_entrega = :tiempo, condiciones_pago = :condiciones,
                      subtotal = :sub, monto_iva = :iva, total = :total
                      WHERE id = :id";
        $stmt = $pdo->prepare($sqlUpdate);
        $stmt->execute([
            ':cliente' => $data['cliente_empresa'], ':acro' => $acronimo,
            ':contacto' => $data['cliente_contacto'], ':correo' => $data['cliente_correo'],
            ':ref' => $data['ref_general'], ':lugar' => $data['lugar_entrega'], 
            ':tiempo' => $data['tiempo_entrega'], ':condiciones' => $data['condiciones_pago'], 
            ':sub' => $data['subtotal'], ':iva' => $data['monto_iva'],
            ':total' => $data['total'], ':id' => $quoteId
        ]);

        // Borramos detalles viejos para reinsertar los nuevos
        $pdo->prepare("DELETE FROM detalles_cotizacion WHERE cotizacion_id = ?")->execute([$quoteId]);

    } else {
        // NUEVA COTIZACIÓN
        $sqlSeq = "SELECT MAX(consecutivo_cliente) FROM cotizaciones WHERE cliente_acronimo = :acro";
        $stmtSeq = $pdo->prepare($sqlSeq);
        $stmtSeq->execute([':acro' => $acronimo]);
        $ultimo = $stmtSeq->fetchColumn();
        $nuevo_consecutivo = $ultimo ? ($ultimo + 1) : 1;

        $token = bin2hex(random_bytes(16));
        $sqlInsert = "INSERT INTO cotizaciones 
                     (vendedor_id, cliente_empresa, cliente_acronimo, consecutivo_cliente, 
                      cliente_contacto, cliente_correo, ref_general, lugar_entrega, tiempo_entrega, condiciones_pago, subtotal, monto_iva, total, token_url) 
                     VALUES (:uid, :cliente, :acro, :consec, 
                             :contacto, :correo, :ref, :lugar, :tiempo, :condiciones, :sub, :iva, :total, :token)";
        $stmt = $pdo->prepare($sqlInsert);
        $stmt->execute([
            ':uid' => $_SESSION['user_id'], ':cliente' => $data['cliente_empresa'], 
            ':acro' => $acronimo, ':consec' => $nuevo_consecutivo,
            ':contacto' => $data['cliente_contacto'], ':correo' => $data['cliente_correo'], ':ref' => $data['ref_general'], 
            ':lugar' => $data['lugar_entrega'], ':tiempo' => $data['tiempo_entrega'], ':condiciones' => $data['condiciones_pago'], 
            ':sub' => $data['subtotal'], ':iva' => $data['monto_iva'], ':total' => $data['total'],
            ':token' => $token
        ]);
        $quoteId = $pdo->lastInsertId();
        
        // Guardar cliente para autocompletado
        $sqlCli = "INSERT INTO clientes (empresa, acronimo, contacto, correo, lugar_entrega, tiempo_entrega, condiciones_pago) 
                   VALUES (:emp, :acr, :cont, :corr, :lug, :tiem, :cond) 
                   ON DUPLICATE KEY UPDATE contacto=VALUES(contacto), correo=VALUES(correo)";
        $stmtC = $pdo->prepare($sqlCli);
        $stmtC->execute([
            ':emp' => $data['cliente_empresa'], ':acr' => $acronimo,
            ':cont' => $data['cliente_contacto'], ':corr' => $data['cliente_correo'],
            ':lug' => $data['lugar_entrega'], ':tiem' => $data['tiempo_entrega'], ':cond' => $data['condiciones_pago']
        ]);
    }

    // Insertar Partidas
    $sqlItem = "INSERT INTO detalles_cotizacion (cotizacion_id, cantidad, unidad_medida, descripcion, precio_unitario, total_linea) 
                VALUES (:qid, :cant, :unidad, :desc, :precio, :tot)";
    $stmtItem = $pdo->prepare($sqlItem);

    if(isset($data['items']) && is_array($data['items'])) {
        foreach ($data['items'] as $item) {
            $stmtItem->execute([
                ':qid' => $quoteId, ':cant' => $item['cantidad'], ':unidad' => $item['unidad'],
                ':desc' => $item['nombre'], ':precio' => $item['precio'], ':tot' => $item['total_linea']
            ]);
        }
    }

    // --- PROCESAMIENTO DE FOTOS (NUEVO) ---
    if (isset($_FILES['fotos'])) {
        $files = $_FILES['fotos'];
        // Ajusta ruta según tu estructura. Si este archivo está en /php, sube a ../assets/uploads
        $uploadDir = '../assets/uploads/'; 
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $stmtFoto = $pdo->prepare("INSERT INTO cotizacion_fotos (cotizacion_id, ruta_foto, nombre_original) VALUES (?, ?, ?)");

        // Manejo de múltiples archivos
        // $_FILES['fotos']['name'] puede ser un array o un string único dependiendo de cómo se envíe, 
        // pero con 'multiple' en HTML suele ser array.
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $files['tmp_name'][$i];
                    $name = basename($files['name'][$i]);
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    
                    if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $newName = uniqid('img_') . '.' . $ext;
                        if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                            // Guardamos la ruta relativa para usarla en el <img src>
                            $stmtFoto->execute([$quoteId, 'assets/uploads/' . $newName, $name]);
                        }
                    }
                }
            }
        }
    }
    // -------------------------------------

    $pdo->commit();
    echo json_encode(["success" => true, "id" => $quoteId, "message" => "Guardado correctamente"]);

} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>