<?php
// editar_cotizacion.php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require 'config/conexion.php'; 

if (!isset($_GET['id'])) { header('Location: dashboard.php'); exit; }
$id = $_GET['id'];
$uid = $_SESSION['user_id'];
$rol = $_SESSION['user_rol'];

// 1. OBTENER COTIZACIÓN (Validando permisos)
$sql = "SELECT * FROM cotizaciones WHERE id = :id";
if ($rol !== 'administrador') $sql .= " AND vendedor_id = :uid";
$stmt = $pdo->prepare($sql);
$params = [':id' => $id];
if ($rol !== 'administrador') $params[':uid'] = $uid;
$stmt->execute($params);
$cot = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cot) { die("Cotización no encontrada o sin permiso."); }

// 2. OBTENER DETALLES (ITEMS)
$stmtItems = $pdo->prepare("SELECT * FROM detalles_cotizacion WHERE cotizacion_id = :id");
$stmtItems->execute([':id' => $id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// 3. OBTENER FOTOS (EVIDENCIAS) - ¡NUEVO!
$stmtFotos = $pdo->prepare("SELECT * FROM cotizacion_fotos WHERE cotizacion_id = :id");
$stmtFotos->execute([':id' => $id]);
$fotos = $stmtFotos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cotización #<?php echo $cot['consecutivo_cliente']; ?> | Maquinados Cardona</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] }, colors: { brand: { yellow: '#FDB913', black: '#111111' } } } } }
    </script>
    <style>input:focus, select:focus, textarea:focus { outline: none; border-color: #FDB913; box-shadow: 0 0 0 1px #FDB913; } .loader { border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; border-top: 2px solid #FDB913; width: 16px; height: 16px; animation: spin 1s linear infinite; } @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

    <nav class="bg-brand-black text-white px-6 py-4 shadow-md sticky top-0 z-50 flex justify-between items-center border-b-4 border-brand-yellow">
        <div class="flex items-center gap-3">
             <a href="dashboard.php" class="hover:text-brand-yellow transition"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg></a>
            <div>
                <h1 class="font-bold text-lg tracking-wide uppercase">EDITAR COTIZACIÓN</h1>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest">FOLIO: <?php echo 'MB-'.str_pad($cot['consecutivo_cliente'], 3, "0", STR_PAD_LEFT).$cot['cliente_acronimo']; ?></p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="generar_pdf.php?id=<?php echo $id; ?>" target="_blank" class="bg-white text-black px-3 py-1 rounded text-xs font-bold hover:bg-gray-200">VER PDF</a>
        </div>
    </nav>

    <main class="flex-1 max-w-7xl mx-auto w-full p-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100">
                <input type="hidden" id="quote-id" value="<?php echo $cot['id']; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Referencia</label>
                        <input type="text" id="input-ref" value="<?php echo htmlspecialchars($cot['ref_general']); ?>" class="w-full bg-yellow-50 border border-yellow-200 text-gray-900 text-sm rounded p-2.5 font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Fecha</label>
                        <input type="text" disabled value="<?php echo date('d/m/Y', strtotime($cot['fecha_emision'])); ?>" class="w-full bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded p-2.5 cursor-not-allowed">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Cliente</label>
                        <div class="flex gap-2">
                            <input type="text" id="input-acronym" value="<?php echo htmlspecialchars($cot['cliente_acronimo']); ?>" class="w-1/4 border-2 border-brand-yellow p-2.5 rounded text-sm font-bold text-center uppercase" readonly>
                            <input type="text" id="input-client" value="<?php echo htmlspecialchars($cot['cliente_empresa']); ?>" class="w-3/4 bg-white border border-gray-300 text-gray-900 text-sm rounded p-2.5" placeholder="Cliente">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Atención A</label>
                        <input type="text" id="input-contact" value="<?php echo htmlspecialchars($cot['cliente_contacto']); ?>" class="w-full bg-white border border-gray-300 text-sm rounded p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Correo</label>
                        <input type="email" id="input-email" value="<?php echo htmlspecialchars($cot['cliente_correo']); ?>" class="w-full bg-white border border-gray-300 text-sm rounded p-2.5">
                    </div>
                </div>

                <div class="mt-6 border-t pt-4">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-3">Galería de Evidencias (Interno)</label>
                    
                    <?php if(count($fotos) > 0): ?>
                        <div class="flex flex-wrap gap-4 mb-4">
                            <?php foreach($fotos as $foto): ?>
                                <div class="relative group border rounded p-1 shadow-sm hover:shadow-md transition bg-gray-50">
                                    <a href="<?php echo htmlspecialchars($foto['ruta_foto']); ?>" target="_blank">
                                        <img src="<?php echo htmlspecialchars($foto['ruta_foto']); ?>" class="h-20 w-20 object-cover rounded cursor-pointer">
                                    </a>
                                    <div class="text-[9px] text-gray-400 mt-1 truncate w-20 text-center"><?php echo htmlspecialchars($foto['nombre_original']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-xs text-gray-400 italic mb-4">No hay fotos adjuntas a esta cotización.</p>
                    <?php endif; ?>

                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Agregar más fotos:</label>
                    <input type="file" id="input-fotos" multiple accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-brand-yellow file:text-black hover:file:bg-yellow-400"/>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Lugar Entrega</label>
                    <input type="text" id="input-place" value="<?php echo htmlspecialchars($cot['lugar_entrega']); ?>" class="w-full border-gray-300 rounded text-xs p-2 border">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Tiempo Entrega</label>
                    <input type="text" id="input-delivery" value="<?php echo htmlspecialchars($cot['tiempo_entrega']); ?>" class="w-full border-gray-300 rounded text-xs p-2 border">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Condiciones Pago</label>
                    <input type="text" id="input-payment" value="<?php echo htmlspecialchars($cot['condiciones_pago']); ?>" class="w-full border-gray-300 rounded text-xs p-2 border">
                </div>
            </div>

            <div class="p-6">
                <div class="flex flex-col md:flex-row gap-2 items-end mb-6 bg-gray-100 p-3 rounded border border-gray-200">
                    <div class="w-full md:w-20"><label class="block text-[10px] font-bold uppercase mb-1">Cant.</label><input type="number" id="new-qty" value="1" class="w-full p-2 text-sm border rounded text-center"></div>
                    <div class="w-full md:w-32"><label class="block text-[10px] font-bold uppercase mb-1">Unidad</label><select id="new-unit" class="w-full p-2 text-sm border rounded bg-white"><option>PIEZA</option><option>SERVICIO</option><option>JUEGO</option><option>LOTE</option><option>HORA</option><option>KG</option><option>METRO</option></select></div>
                    <div class="flex-1 w-full"><label class="block text-[10px] font-bold uppercase mb-1">Descripción</label><input type="text" id="new-desc" class="w-full p-2 text-sm border rounded"></div>
                    <div class="w-full md:w-32"><label class="block text-[10px] font-bold uppercase mb-1">P. Unit.</label><input type="number" id="new-price" class="w-full p-2 text-sm border rounded text-right"></div>
                    <button onclick="addManualItem()" class="px-4 py-2 bg-black text-white text-sm font-bold rounded hover:bg-brand-yellow hover:text-black transition">AGREGAR</button>
                </div>

                <table class="w-full text-sm text-left border rounded"><tbody id="quote-table-body" class="divide-y divide-gray-100"></tbody></table>

                <div class="mt-6 flex flex-col md:flex-row justify-between items-start gap-8">
                    <div class="flex-1 bg-gray-50 p-4 rounded w-full"><span class="text-[10px] font-bold text-gray-400 uppercase">Importe con Letra</span><p id="amount-text" class="text-sm font-medium uppercase">CERO PESOS</p></div>
                    <div class="w-full md:w-72">
                        <div class="flex justify-between py-1"><span class="text-gray-500">Subtotal:</span><span id="subtotal-display" class="font-bold">$0.00</span></div>
                        <div class="flex justify-between py-1"><span class="text-gray-500">IVA (16%):</span><span id="iva-display" class="font-bold">$0.00</span></div>
                        <div class="flex justify-between py-3 mt-2 border-t border-dashed border-gray-300"><span class="text-lg font-bold">TOTAL:</span><span id="total-display" class="text-lg font-bold">$0.00</span></div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <a href="dashboard.php" class="px-6 py-3 rounded text-sm font-bold text-gray-500 hover:text-black transition">CANCELAR</a>
                <button onclick="updateQuote()" id="btn-save" class="bg-brand-yellow text-black px-8 py-3 rounded shadow hover:bg-yellow-400 font-bold text-sm flex gap-2 items-center">
                    <span id="btn-text">GUARDAR CAMBIOS</span>
                </button>
            </div>
        </div>
    </main>

    <script>
        // Cargar items desde PHP a JS
        let cart = <?php 
            $jsItems = [];
            foreach($items as $i) {
                $jsItems[] = [
                    'name' => $i['descripcion'],
                    'qty' => (float)$i['cantidad'],
                    'unit' => $i['unidad_medida'],
                    'price' => (float)$i['precio_unitario']
                ];
            }
            echo json_encode($jsItems);
        ?>;

        window.onload = () => { renderCart(); };

        function addManualItem() {
            const qty = parseFloat(document.getElementById('new-qty').value) || 0;
            const unit = document.getElementById('new-unit').value;
            const desc = document.getElementById('new-desc').value.trim();
            const price = parseFloat(document.getElementById('new-price').value) || 0;

            if (qty <= 0 || desc === "" || price <= 0) { alert("Revise los datos del concepto."); return; }
            cart.push({ name: desc, qty: qty, unit: unit, price: price });
            document.getElementById('new-desc').value = ""; document.getElementById('new-price').value = "";
            renderCart();
        }

        function renderCart() {
            const tbody = document.getElementById('quote-table-body');
            tbody.innerHTML = '';
            let subtotal = 0;
            cart.forEach((item, i) => {
                const totalLine = item.qty * item.price;
                subtotal += totalLine;
                tbody.innerHTML += `
                    <tr class="hover:bg-yellow-50 transition">
                        <td class="px-4 py-3 text-center">${item.qty}</td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500">${item.unit}</td>
                        <td class="px-4 py-3 font-medium">${item.name}</td>
                        <td class="px-4 py-3 text-right">$${item.price.toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                        <td class="px-4 py-3 text-right font-bold">$${totalLine.toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                        <td class="px-2 py-3 text-center"><button onclick="cart.splice(${i},1); renderCart();" class="text-red-400 hover:text-red-600">×</button></td>
                    </tr>`;
            });
            const iva = subtotal * 0.16; const total = subtotal + iva;
            document.getElementById('subtotal-display').innerText = fmtMoney(subtotal);
            document.getElementById('iva-display').innerText = fmtMoney(iva);
            document.getElementById('total-display').innerText = fmtMoney(total);
            document.getElementById('amount-text').innerText = numeroALetras(total);
        }

        function updateQuote() {
            if(cart.length === 0) { alert("La cotización no puede estar vacía."); return; }
            
            const btn = document.getElementById('btn-save');
            const btnText = document.getElementById('btn-text');
            btnText.innerText = "GUARDANDO...";
            btn.disabled = true;

            const subtotal = cart.reduce((sum, i) => sum + (i.qty * i.price), 0);
            const formData = new FormData();
            
            // ID es crucial para editar
            formData.append('id', document.getElementById('quote-id').value);
            
            formData.append('cliente_empresa', document.getElementById('input-client').value);
            formData.append('cliente_acronimo', document.getElementById('input-acronym').value);
            formData.append('cliente_contacto', document.getElementById('input-contact').value);
            formData.append('cliente_correo', document.getElementById('input-email').value);
            formData.append('ref_general', document.getElementById('input-ref').value);
            formData.append('lugar_entrega', document.getElementById('input-place').value);
            formData.append('tiempo_entrega', document.getElementById('input-delivery').value);
            formData.append('condiciones_pago', document.getElementById('input-payment').value);
            formData.append('subtotal', subtotal);
            formData.append('monto_iva', subtotal * 0.16);
            formData.append('total', subtotal * 1.16);
            formData.append('items', JSON.stringify(cart)); // Items limpios

            // Nuevas fotos
            const fileInput = document.getElementById('input-fotos');
            for (let i = 0; i < fileInput.files.length; i++) {
                formData.append('fotos[]', fileInput.files[i]);
            }

            fetch('php/guardar_cotizacion.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    alert("Cotización actualizada correctamente.");
                    window.location.href = 'dashboard.php';
                } else {
                    alert("Error: " + res.message);
                    btn.disabled = false; btnText.innerText = "GUARDAR CAMBIOS";
                }
            })
            .catch(e => { alert("Error de red: " + e); btn.disabled = false; });
        }

        function fmtMoney(n) { return '$' + n.toLocaleString('es-MX', {minimumFractionDigits: 2}); }
        function numeroALetras(n) { return "SON: " + n.toFixed(2) + " PESOS"; } // Simplificado para visualización rápida
    </script>
</body>
</html>