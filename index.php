<?php
// index.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Conexión a la base de datos
require 'config/conexion.php'; 

// Configuración regional
date_default_timezone_set('America/Mexico_City');
$fecha_hoy = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotizador | Maquinados Cardona</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: {
                            yellow: '#FDB913',
                            black: '#111111',
                            gray: '#F3F4F6'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #FDB913;
            box-shadow: 0 0 0 1px #FDB913;
        }
        .loader {
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top: 2px solid #FDB913;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

    <nav class="bg-brand-black text-white px-6 py-4 shadow-md sticky top-0 z-50 flex justify-between items-center border-b-4 border-brand-yellow">
        <div class="flex items-center gap-3">
            <div class="h-8 w-8 bg-brand-yellow text-black flex items-center justify-center font-bold rounded-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.41.41.43 1.059.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.31.391.29 1.04-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.02-.398-1.11-.94l-.149-.894c-.07-.424-.384-.764-.78-.93-.398-.164-.855-.142-1.204.108l-.738.527c-.391.31-1.04.29-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <div>
                <h1 class="font-bold text-lg tracking-wide uppercase">Maquinados Cardona</h1>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest">Nueva Cotización</p>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <a href="dashboard.php" class="text-xs font-bold text-gray-300 hover:text-brand-yellow transition flex items-center gap-1 uppercase tracking-wide">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Ver Historial
            </a>
            <span class="text-gray-600">|</span>
            <button onclick="clearData()" class="flex items-center gap-2 text-xs bg-gray-800 hover:bg-gray-700 text-white px-3 py-2 rounded transition border border-gray-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Limpiar
            </button>
        </div>
    </nav>

    <main class="flex-1 max-w-7xl mx-auto w-full p-6">
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-8">
            
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center mb-4 justify-between">
                    <h2 class="text-sm font-bold uppercase text-brand-black border-l-4 border-brand-yellow pl-3">Datos del Proyecto</h2>
                    <span id="save-msg" class="text-xs text-green-600 font-bold opacity-0 transition-opacity duration-500">
                        ✓ Borrador Guardado
                    </span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Referencia (REF)</label>
                        <input type="text" id="input-ref" oninput="saveLocalData()" class="w-full bg-yellow-50 border border-yellow-200 text-gray-900 text-sm rounded p-2.5 font-bold placeholder-gray-400" placeholder="Ej. 25118">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Fecha Emisión</label>
                        <input type="text" disabled value="<?php echo $fecha_hoy; ?>" class="w-full bg-gray-100 border border-gray-300 text-gray-500 text-sm rounded p-2.5 cursor-not-allowed">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Cliente / Empresa</label>
                        <div class="flex gap-2">
                            <div class="w-1/3 md:w-1/4">
                                <input type="text" id="input-acronym" oninput="this.value = this.value.toUpperCase(); saveLocalData();" 
                                       class="w-full border-2 border-brand-yellow p-2.5 rounded text-sm font-bold text-center focus:outline-none focus:ring-2 focus:ring-yellow-200 placeholder-gray-300 uppercase" 
                                       placeholder="ACRÓNIMO" maxlength="10" title="Sufijo para el folio (Ej. CHAS)">
                            </div>
                            <div class="w-2/3 md:w-3/4">
                                <input type="text" id="input-client" list="list-clientes" 
                                       oninput="buscarCliente(); suggestAcronym(); saveLocalData();" 
                                       onchange="seleccionarCliente()"
                                       class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded p-2.5" 
                                       placeholder="Escribe para buscar o crear nuevo..." autocomplete="off">
                                <datalist id="list-clientes"></datalist>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1 ml-1">Folio generado será: <b>MB-XXX<span id="preview-acronym">ACRO</span></b></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Atención A (Contacto)</label>
                        <input type="text" id="input-contact" oninput="saveLocalData()" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded p-2.5" placeholder="Nombre del contacto directo">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Correo Electrónico</label>
                        <input type="email" id="input-email" oninput="saveLocalData()" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded p-2.5" placeholder="contacto@cliente.com">
                    </div>
                </div>

                <div class="mt-6 border-t pt-4">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Evidencias / Bocetos (Interno)</label>
                    <input type="file" id="input-fotos" multiple accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-brand-yellow file:text-black hover:file:bg-yellow-400"/>
                    <p class="text-[10px] text-gray-400 mt-1">* Estas fotos NO saldrán en el PDF, solo son para consulta interna.</p>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Lugar de Entrega</label>
                    <input type="text" id="input-place" oninput="saveLocalData()" class="w-full bg-white border border-gray-300 text-xs rounded p-2" placeholder="Ej. Planta Cliente">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Tiempo de Entrega</label>
                    <input type="text" id="input-delivery" oninput="saveLocalData()" class="w-full bg-white border border-gray-300 text-xs rounded p-2" placeholder="Ej. 2 Días hábiles">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Condiciones de Pago</label>
                    <input type="text" id="input-payment" oninput="saveLocalData()" class="w-full bg-white border border-gray-300 text-xs rounded p-2" placeholder="Ej. Crédito 15 días">
                </div>
            </div>

            <div class="p-6">
                <div class="flex flex-col md:flex-row gap-2 items-end mb-6 bg-gray-100 p-3 rounded border border-gray-200">
                    <div class="w-full md:w-20">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Cant.</label>
                        <input type="number" id="new-qty" value="1" min="1" class="w-full border-gray-300 rounded text-sm p-2 border text-center">
                    </div>
                    <div class="w-full md:w-32">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Unidad</label>
                        <select id="new-unit" class="w-full border-gray-300 rounded text-sm p-2 border bg-white">
                            <option value="PIEZA">PIEZA</option>
                            <option value="SERVICIO">SERVICIO</option>
                            <option value="JUEGO">JUEGO</option>
                            <option value="LOTE">LOTE</option>
                            <option value="HORA">HORA</option>
                            <option value="KG">KG</option>
                            <option value="METRO">METRO</option>
                        </select>
                    </div>
                    <div class="flex-1 w-full">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Descripción</label>
                        <input type="text" id="new-desc" class="w-full border-gray-300 rounded text-sm p-2 border" placeholder="Describa el maquinado o servicio...">
                    </div>
                    <div class="w-full md:w-32">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">P. Unitario ($)</label>
                        <input type="number" id="new-price" step="0.01" class="w-full border-gray-300 rounded text-sm p-2 border text-right" placeholder="0.00">
                    </div>
                    <div class="w-full md:w-auto">
                        <button onclick="addManualItem()" class="h-[38px] px-6 bg-brand-black text-white text-sm font-bold rounded hover:bg-brand-yellow hover:text-black transition uppercase tracking-wider w-full md:w-auto">
                            Agregar
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase text-black bg-brand-yellow font-bold">
                            <tr>
                                <th class="px-4 py-3 text-center w-16 border-r border-yellow-500">Cant</th>
                                <th class="px-4 py-3 text-center w-24 border-r border-yellow-500">Unidad</th>
                                <th class="px-4 py-3 border-r border-yellow-500">Descripción</th>
                                <th class="px-4 py-3 text-right w-32 border-r border-yellow-500">P. Unitario</th>
                                <th class="px-4 py-3 text-right w-32 border-r border-yellow-500">Total</th>
                                <th class="px-2 py-3 text-center w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="quote-table-body" class="bg-white divide-y divide-gray-100"></tbody>
                    </table>
                </div>

                <div class="mt-6 flex flex-col md:flex-row justify-between items-start gap-8">
                    <div class="flex-1 bg-gray-50 p-4 rounded border border-gray-200 w-full">
                        <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Importe con Letra</span>
                        <p id="amount-text" class="text-sm font-medium text-gray-800 uppercase">CERO PESOS 00/100 M.N.</p>
                    </div>

                    <div class="w-full md:w-72">
                        <div class="flex justify-between py-1">
                            <span class="text-gray-500 text-sm">Subtotal:</span>
                            <span id="subtotal-display" class="font-bold text-gray-800">$0.00</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-500 text-sm">IVA (16%):</span>
                            <span id="iva-display" class="font-bold text-gray-800">$0.00</span>
                        </div>
                        <div class="flex justify-between py-3 mt-2 border-t border-dashed border-gray-300">
                            <span class="text-lg font-bold text-brand-black">TOTAL:</span>
                            <span id="total-display" class="text-lg font-bold text-brand-black">$0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
                <button onclick="saveAndGenerate()" id="btn-generate" class="group relative flex items-center justify-center gap-2 bg-brand-black text-white px-8 py-3 rounded shadow hover:bg-brand-yellow hover:text-black transition-all duration-300 font-bold tracking-wide text-sm">
                    <span id="btn-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    </span>
                    <span id="btn-text">GENERAR COTIZACIÓN PDF</span>
                </button>
            </div>
        </div>
        
        <p class="text-center text-xs text-gray-400 mt-6">&copy; <?php echo date('Y'); ?> Maquinados Industriales Cardona. Todos los derechos reservados.</p>
    </main>

    <script>
        let cart = [];
        let clientesEncontrados = []; // Para autocompletado

        window.onload = () => { loadLocalData(); };

        // --- FUNCIONES DE AUTOCOMPLETADO ---
        function buscarCliente() {
            const input = document.getElementById('input-client');
            const termino = input.value;
            const datalist = document.getElementById('list-clientes');

            if (termino.length < 2) return;

            fetch('php/buscar_cliente.php?term=' + encodeURIComponent(termino))
                .then(res => res.json())
                .then(data => {
                    clientesEncontrados = data;
                    datalist.innerHTML = ''; 
                    data.forEach(c => {
                        const option = document.createElement('option');
                        option.value = c.empresa;
                        datalist.appendChild(option);
                    });
                });
        }

        function seleccionarCliente() {
            const input = document.getElementById('input-client');
            const val = input.value;
            
            const cliente = clientesEncontrados.find(c => c.empresa === val);

            if (cliente) {
                document.getElementById('input-acronym').value = cliente.acronimo || '';
                document.getElementById('preview-acronym').innerText = cliente.acronimo || 'ACRO';
                document.getElementById('input-contact').value = cliente.contacto || '';
                document.getElementById('input-email').value = cliente.correo || '';
                document.getElementById('input-place').value = cliente.lugar_entrega || '';
                document.getElementById('input-delivery').value = cliente.tiempo_entrega || '';
                document.getElementById('input-payment').value = cliente.condiciones_pago || '';
                saveLocalData();
            }
        }

        function suggestAcronym() {
            const acronymInput = document.getElementById('input-acronym');
            const preview = document.getElementById('preview-acronym');
            preview.innerText = acronymInput.value || "ACRO";
        }

        // --- MANEJO DE PARTIDAS ---
        function addManualItem() {
            const qtyInput = document.getElementById('new-qty');
            const unitInput = document.getElementById('new-unit');
            const descInput = document.getElementById('new-desc');
            const priceInput = document.getElementById('new-price');

            const qty = parseFloat(qtyInput.value) || 0;
            const unit = unitInput.value;
            const desc = descInput.value.trim();
            const price = parseFloat(priceInput.value) || 0;

            if (qty <= 0) { alert("La cantidad debe ser mayor a 0."); return; }
            if (desc === "") { alert("Ingrese una descripción."); descInput.focus(); return; }
            if (price <= 0) { alert("Ingrese un precio válido."); priceInput.focus(); return; }

            cart.push({ name: desc, qty: qty, unit: unit, price: price });

            descInput.value = ""; priceInput.value = ""; qtyInput.value = "1"; descInput.focus();
            renderCart(); saveLocalData();
        }

        function renderCart() {
            const tbody = document.getElementById('quote-table-body');
            tbody.innerHTML = '';
            let subtotal = 0;

            if(cart.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-gray-400 text-sm italic bg-gray-50">No hay partidas agregadas.</td></tr>`;
            }

            cart.forEach((item, i) => {
                const totalLine = item.qty * item.price;
                subtotal += totalLine;
                tbody.innerHTML += `
                    <tr class="hover:bg-yellow-50 transition group">
                        <td class="px-4 py-3 text-center text-gray-700 font-medium">${item.qty}</td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500">${item.unit}</td>
                        <td class="px-4 py-3 text-gray-800 font-medium">${item.name}</td>
                        <td class="px-4 py-3 text-right text-gray-600">$${item.price.toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900">$${totalLine.toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                        <td class="px-2 py-3 text-center">
                            <button onclick="removeFromCart(${i})" class="text-gray-300 hover:text-red-500 transition p-1" title="Eliminar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </td>
                    </tr>`;
            });

            const iva = subtotal * 0.16;
            const total = subtotal + iva;

            document.getElementById('subtotal-display').innerText = fmtMoney(subtotal);
            document.getElementById('iva-display').innerText = fmtMoney(iva);
            document.getElementById('total-display').innerText = fmtMoney(total);
            document.getElementById('amount-text').innerText = numeroALetras(total);
        }

        function removeFromCart(idx) {
            cart.splice(idx, 1);
            renderCart();
            saveLocalData();
        }

        // --- GUARDAR Y GENERAR (MODIFICADO PARA FOTOS Y REDIRECCIÓN) ---
        function saveAndGenerate() {
            if(cart.length === 0) { alert("Agregue al menos un concepto."); return; }
            
            const client = document.getElementById('input-client').value;
            const acronym = document.getElementById('input-acronym').value; 
            
            if(!client.trim()) { alert("El nombre del CLIENTE es obligatorio."); document.getElementById('input-client').focus(); return; }

            const btn = document.getElementById('btn-generate');
            const btnText = document.getElementById('btn-text');
            const btnIcon = document.getElementById('btn-icon');
            
            const originalContent = btnText.innerText;
            btnText.innerText = "PROCESANDO...";
            btnIcon.innerHTML = `<div class="loader"></div>`;
            btn.disabled = true;
            btn.classList.add('opacity-80', 'cursor-not-allowed');

            const subtotal = cart.reduce((sum, i) => sum + (i.qty * i.price), 0);
            
            // Usamos FormData para poder enviar archivos (fotos) y datos
            const formData = new FormData();
            formData.append('cliente_empresa', client);
            formData.append('cliente_acronimo', acronym);
            formData.append('cliente_contacto', document.getElementById('input-contact').value);
            formData.append('cliente_correo', document.getElementById('input-email').value);
            formData.append('ref_general', document.getElementById('input-ref').value);
            formData.append('lugar_entrega', document.getElementById('input-place').value);
            formData.append('tiempo_entrega', document.getElementById('input-delivery').value);
            formData.append('condiciones_pago', document.getElementById('input-payment').value);
            formData.append('subtotal', subtotal);
            formData.append('monto_iva', subtotal * 0.16);
            formData.append('total', subtotal * 1.16);
            
            // Los items van como JSON string
            formData.append('items', JSON.stringify(cart.map(item => ({
                nombre: item.name,
                cantidad: item.qty,
                unidad: item.unit,
                precio: item.price,
                total_linea: item.qty * item.price
            }))));

            // Agregar Fotos al FormData
            const fileInput = document.getElementById('input-fotos');
            for (let i = 0; i < fileInput.files.length; i++) {
                formData.append('fotos[]', fileInput.files[i]);
            }

            fetch('php/guardar_cotizacion.php', {
                method: 'POST',
                body: formData // Enviamos FormData en lugar de JSON string
            })
            .then(res => res.json())
            .then(response => {
                if(response.success) {
                    // 1. Abrir PDF en pestaña nueva
                    window.open('generar_pdf.php?id=' + response.id, '_blank');
                    
                    // 2. Flujo de envío de correo y limpieza
                    setTimeout(() => {
                        if(confirm("¡Cotización generada exitosamente!\n\n¿Desea enviar esta cotización por correo ahora mismo?")) {
                            let defaultEmail = document.getElementById('input-email').value;
                            let emailDestino = prompt("Ingrese el correo del destinatario:", defaultEmail);
                            
                            if(emailDestino && emailDestino.trim() !== "") {
                                btnText.innerText = "ENVIANDO CORREO...";
                                
                                fetch('php/enviar_correo.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        id: response.id,
                                        email: emailDestino
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    alert(data.message);
                                })
                                .catch(err => alert("Error al enviar correo: " + err))
                                .finally(() => {
                                    window.location.href = 'dashboard.php'; // Redirigir siempre al final
                                });
                            } else {
                                window.location.href = 'dashboard.php'; // Canceló correo, redirigir
                            }
                        } else {
                            window.location.href = 'dashboard.php'; // Dijo NO al correo, redirigir
                        }
                    }, 500); 

                } else {
                    alert("Error: " + response.message);
                    btnText.innerText = originalContent;
                    btnIcon.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>`;
                    btn.disabled = false;
                    btn.classList.remove('opacity-80', 'cursor-not-allowed');
                }
            })
            .catch(err => alert("Error de conexión: " + err));
        }

        function fmtMoney(n) { return '$' + n.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }

        function saveLocalData() {
            localStorage.setItem('mic_cart', JSON.stringify(cart));
            localStorage.setItem('mic_ref', document.getElementById('input-ref').value);
            localStorage.setItem('mic_client', document.getElementById('input-client').value);
            localStorage.setItem('mic_acronym', document.getElementById('input-acronym').value);
            localStorage.setItem('mic_contact', document.getElementById('input-contact').value);
            localStorage.setItem('mic_email', document.getElementById('input-email').value);
            localStorage.setItem('mic_place', document.getElementById('input-place').value);
            localStorage.setItem('mic_delivery', document.getElementById('input-delivery').value);
            localStorage.setItem('mic_payment', document.getElementById('input-payment').value);
            
            const msg = document.getElementById('save-msg');
            msg.classList.remove('opacity-0');
            setTimeout(() => msg.classList.add('opacity-0'), 2000);
            suggestAcronym();
        }

        function loadLocalData() {
            if(localStorage.getItem('mic_cart')) { try { cart = JSON.parse(localStorage.getItem('mic_cart')); renderCart(); } catch(e) {} }
            if(localStorage.getItem('mic_ref')) document.getElementById('input-ref').value = localStorage.getItem('mic_ref');
            if(localStorage.getItem('mic_client')) document.getElementById('input-client').value = localStorage.getItem('mic_client');
            if(localStorage.getItem('mic_acronym')) document.getElementById('input-acronym').value = localStorage.getItem('mic_acronym');
            if(localStorage.getItem('mic_contact')) document.getElementById('input-contact').value = localStorage.getItem('mic_contact');
            if(localStorage.getItem('mic_email')) document.getElementById('input-email').value = localStorage.getItem('mic_email');
            if(localStorage.getItem('mic_place')) document.getElementById('input-place').value = localStorage.getItem('mic_place');
            if(localStorage.getItem('mic_delivery')) document.getElementById('input-delivery').value = localStorage.getItem('mic_delivery');
            if(localStorage.getItem('mic_payment')) document.getElementById('input-payment').value = localStorage.getItem('mic_payment');
            suggestAcronym();
        }

        function clearData() {
            if(confirm("¿Borrar todos los datos actuales y empezar de cero?")) {
                localStorage.removeItem('mic_cart');
                localStorage.clear();
                location.reload();
            }
        }

        function numeroALetras(num) {
            if(num === 0) return "CERO PESOS 00/100 M.N.";
            const enteros = Math.floor(num);
            const centavos = Math.round((num - enteros) * 100);
            const u = ["", "UN ", "DOS ", "TRES ", "CUATRO ", "CINCO ", "SEIS ", "SIETE ", "OCHO ", "NUEVE "];
            const d = ["", "DIEZ ", "VEINTE ", "TREINTA ", "CUARENTA ", "CINCUENTA ", "SESENTA ", "SETENTA ", "OCHENTA ", "NOVENTA "];
            const dv = ["DIEZ ", "ONCE ", "DOCE ", "TRECE ", "CATORCE ", "QUINCE ", "DIECISEIS ", "DIECISIETE ", "DIECIOCHO ", "DIECINUEVE "];
            const c = ["", "CIENTO ", "DOSCIENTOS ", "TRESCIENTOS ", "CUATROCIENTOS ", "QUINIENTOS ", "SEISCIENTOS ", "SETECIENTOS ", "OCHOCIENTOS ", "NOVECIENTOS "];
            function conv(n) {
                if(n === 100) return "CIEN ";
                if(n > 99) return c[Math.floor(n/100)] + conv(n%100);
                if(n > 19) return d[Math.floor(n/10)] + (n%10 > 0 ? "Y " : "") + u[n%10];
                if(n > 9) return dv[n-10];
                return u[n];
            }
            let letras = "";
            if(enteros >= 1000) {
                let miles = Math.floor(enteros/1000);
                if(miles === 1) letras += "MIL "; else letras += conv(miles) + "MIL ";
            }
            letras += conv(enteros % 1000);
            return `${letras.trim()} PESOS ${centavos.toString().padStart(2,'0')}/100 M.N.`;
        }
    </script>
</body>
</html>