<?php
// vendedores.php
session_start();
// RUTA ACTUALIZADA
require 'config/conexion.php';

// SOLO ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: index.php');
    exit;
}

$msg = "";

// CREAR VENDEDOR
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create') {
    $nombre = trim($_POST['nombre']);
    $email  = trim($_POST['email']);
    $pass   = trim($_POST['password']);
    
    // Datos Fiscales del Vendedor para el PDF
    $rfc    = strtoupper(trim($_POST['rfc']));
    $calle  = trim($_POST['calle']);
    $cp     = trim($_POST['cp']);
    $ciudad = trim($_POST['ciudad']);

    // Validar correo duplicado
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo_electronico = :e");
    $stmt->execute([':e' => $email]);
    if ($stmt->fetch()) {
        $msg = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Error: El correo ya está registrado.</div>";
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $sqlInsert = "INSERT INTO usuarios (nombre_completo, correo_electronico, password_hash, rol, rfc, direccion_calle, codigo_postal, ciudad, estado) 
                      VALUES (:nom, :email, :pass, 'vendedor', :rfc, :calle, :cp, :ciudad, 1)";
        try {
            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([':nom'=>$nombre, ':email'=>$email, ':pass'=>$hash, ':rfc'=>$rfc, ':calle'=>$calle, ':cp'=>$cp, ':ciudad'=>$ciudad]);
            $msg = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>¡Vendedor creado correctamente!</div>";
        } catch (Exception $e) {
            $msg = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Error BD: " . $e->getMessage() . "</div>";
        }
    }
}

// ACTIVAR/DESACTIVAR
if (isset($_GET['toggle_id'])) {
    $idToggle = $_GET['toggle_id'];
    if ($idToggle != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE usuarios SET estado = NOT estado WHERE id = :id");
        $stmt->execute([':id' => $idToggle]);
        header('Location: vendedores.php'); exit;
    }
}

// LISTAR VENDEDORES
$vendedores = $pdo->query("SELECT * FROM usuarios WHERE rol = 'vendedor' ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión Vendedores</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] }, colors: { brand: { yellow: '#FDB913' } } } } }
    </script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

    <nav class="bg-black text-white px-6 py-4 border-b-4 border-brand-yellow flex justify-between items-center">
        <div class="font-bold uppercase tracking-wider flex items-center gap-2">
            <span class="text-brand-yellow">ADMINISTRACIÓN</span> VENDEDORES
        </div>
        <div class="flex gap-4 text-xs">
            <a href="dashboard.php" class="hover:text-brand-yellow transition">Volver al Dashboard</a>
            <span class="text-gray-500">|</span>
            <a href="php/logout.php" class="text-red-400">Salir</a>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto p-6 w-full">
        <?php echo $msg; ?>

        <div class="bg-white p-6 rounded shadow-sm border border-gray-200 mb-8">
            <h2 class="font-bold text-lg mb-4 text-gray-800 border-l-4 border-brand-yellow pl-2">Nuevo Vendedor</h2>
            <form method="POST" action="vendedores.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="create">
                
                <div class="md:col-span-2 text-xs font-bold text-gray-400 uppercase mt-2">1. Acceso al Sistema</div>
                <input type="text" name="nombre" required class="border p-2 rounded text-sm bg-gray-50" placeholder="Nombre Completo">
                <input type="email" name="email" required class="border p-2 rounded text-sm bg-gray-50" placeholder="Correo Electrónico">
                <input type="password" name="password" required class="border p-2 rounded text-sm bg-gray-50 md:col-span-2" placeholder="Contraseña">

                <div class="md:col-span-2 text-xs font-bold text-gray-400 uppercase mt-4">2. Datos Fiscales (Para el PDF)</div>
                <input type="text" name="rfc" required class="border p-2 rounded text-sm bg-gray-50 uppercase" placeholder="RFC">
                <input type="text" name="cp" required class="border p-2 rounded text-sm bg-gray-50" placeholder="Código Postal">
                <input type="text" name="ciudad" required class="border p-2 rounded text-sm bg-gray-50" value="Celaya, Guanajuato, México">
                <input type="text" name="calle" required class="border p-2 rounded text-sm bg-gray-50" placeholder="Calle y Número">

                <div class="md:col-span-2 text-right mt-4">
                    <button type="submit" class="bg-black text-white px-6 py-2 rounded font-bold hover:bg-brand-yellow hover:text-black transition">Guardar Vendedor</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded shadow-sm overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-brand-yellow text-black font-bold text-xs uppercase">
                    <tr>
                        <th class="p-4">Vendedor</th>
                        <th class="p-4">Datos Fiscales</th>
                        <th class="p-4 text-center">Estado</th>
                        <th class="p-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach($vendedores as $v): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="p-4">
                            <div class="font-bold"><?php echo htmlspecialchars($v['nombre_completo']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($v['correo_electronico']); ?></div>
                        </td>
                        <td class="p-4 text-xs text-gray-600">
                            <div>RFC: <?php echo htmlspecialchars($v['rfc']); ?></div>
                            <div><?php echo htmlspecialchars($v['direccion_calle']); ?>, CP <?php echo htmlspecialchars($v['codigo_postal']); ?></div>
                        </td>
                        <td class="p-4 text-center">
                            <span class="px-2 py-1 rounded-full text-[10px] font-bold <?php echo $v['estado']?'bg-green-100 text-green-800':'bg-red-100 text-red-800'; ?>">
                                <?php echo $v['estado'] ? 'ACTIVO' : 'INACTIVO'; ?>
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <a href="vendedores.php?toggle_id=<?php echo $v['id']; ?>" class="underline text-xs">
                                <?php echo $v['estado'] ? 'Desactivar' : 'Activar'; ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>