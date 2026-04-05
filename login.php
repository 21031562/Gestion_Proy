<?php
// login.php
session_start();
require 'config/conexion.php';

$error = '';

// Lógica de Login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $pass  = trim($_POST['password']);

    if (empty($email) || empty($pass)) {
        $error = "Por favor ingrese correo y contraseña.";
    } else {
        // CORRECCIÓN: Usamos 'estado' en lugar de 'activo'
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo_electronico = :email AND estado = 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar contraseña
        if ($user && password_verify($pass, $user['password_hash'])) {
            // Login Exitoso: Guardar datos en sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre_completo'];
            $_SESSION['user_rol'] = $user['rol'];
            
            // Redirigir al dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Credenciales incorrectas o usuario inactivo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Maquinados Cardona</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
        }
        .split-bg {
            background: linear-gradient(to right, #111111 0%, #111111 50%, #FDB913 50%, #FDB913 100%);
        }
        .input-modern {
            border-bottom: 2px solid #e5e7eb;
            transition: border-color 0.3s;
        }
        .input-modern:focus {
            border-bottom-color: #FDB913;
        }
        @media (max-width: 768px) {
            .split-bg {
                background: #111111;
            }
        }
    </style>
</head>
<body class="min-h-screen split-bg flex items-center justify-center p-4">

    <div class="w-full max-w-5xl bg-white shadow-2xl overflow-hidden flex flex-col md:flex-row">
        
        <!-- Panel Izquierdo - Branding -->
        <div class="md:w-1/2 bg-black p-12 flex flex-col justify-center items-center text-center relative overflow-hidden">
            <div class="absolute inset-0 opacity-5">
                <svg class="w-full h-full" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                    </pattern>
                    <rect width="100" height="100" fill="url(#grid)" />
                </svg>
            </div>
            
            <div class="relative z-10">
                <div class="w-20 h-20 bg-[#FDB913] flex items-center justify-center mb-6 mx-auto transform rotate-6">
                    <svg class="w-10 h-10 text-black transform -rotate-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </div>
                
                <h1 class="text-4xl font-bold text-white mb-3">Maquinados<br/>Cardona</h1>
                <div class="w-16 h-1 bg-[#FDB913] mx-auto mb-4"></div>
                <p class="text-gray-400 text-sm max-w-xs mx-auto">Sistema de gestión integral para control de producción y operaciones</p>
            </div>
        </div>

        <!-- Panel Derecho - Formulario -->
        <div class="md:w-1/2 p-12 flex flex-col justify-center">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Bienvenido</h2>
                <p class="text-gray-500">Ingresa tus credenciales para continuar</p>
            </div>

            <?php if($error): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4" role="alert">
                    <p class="text-red-700 text-sm font-medium"><?php echo $error; ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-8">
                <div>
                    <label class="block text-gray-700 text-xs font-semibold mb-3 uppercase tracking-wider" for="email">
                        Correo electrónico
                    </label>
                    <input 
                        class="input-modern appearance-none bg-transparent w-full py-3 px-0 text-gray-900 placeholder-gray-400 leading-tight focus:outline-none" 
                        id="email" 
                        name="email" 
                        type="email" 
                        placeholder="usuario@empresa.com" 
                        required
                    >
                </div>

                <div>
                    <label class="block text-gray-700 text-xs font-semibold mb-3 uppercase tracking-wider" for="password">
                        Contraseña
                    </label>
                    <input 
                        class="input-modern appearance-none bg-transparent w-full py-3 px-0 text-gray-900 placeholder-gray-400 leading-tight focus:outline-none" 
                        id="password" 
                        name="password" 
                        type="password" 
                        placeholder="••••••••••••" 
                        required
                    >
                </div>

                <button 
                    class="w-full bg-black hover:bg-gray-800 text-white font-semibold py-4 px-6 focus:outline-none transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5" 
                    type="submit"
                >
                    INICIAR SESIÓN
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-gray-500 text-sm">
                    ¿Problemas para acceder? <a href="#" class="text-black font-semibold hover:text-[#FDB913] transition-colors">Contacta soporte</a>
                </p>
            </div>
        </div>

    </div>

</body>
</html>