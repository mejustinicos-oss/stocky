// =====================================================
// pages/productos.js
// Lógica específica para la página de productos
// =====================================================

// Variables globales de la página
let productos = [];
let categorias = [];
let presentaciones = [];
let currentEditId = null;

// =====================================================
// INICIALIZACIÓN
// =====================================================
document.addEventListener('DOMContentLoaded', async () => {
    await inicializar();
});

async function inicializar() {
    try {
        // 1. Verificar autenticación
        if (!authService.isAuthenticated()) {
            window.location.href = '../index.html';
            return;
        }

        // 2. Cargar datos iniciales
        await Promise.all([
            cargarProductos(),
            cargarCategorias(),
            cargarPresentaciones()
        ]);

        // 3. Configurar eventos
        configurarEventos();

        // 4. Cargar opciones de select
        cargarOpcionesSelect();

    } catch (error) {
        console.error('Error al inicializar:', error);
        Helpers.showNotification('Error al cargar la página', 'error');
    }
}

// =====================================================
// CARGAR DATOS DESDE LA API
// =====================================================

async function cargarProductos() {
    try {
        const response = await productosService.getAll();
        
        if (response.success) {
            productos = response.data;
            renderTable(productos);
        } else {
            throw new Error(response.message);
        }
    } catch (error) {
        console.error('Error al cargar productos:', error);
        Helpers.showNotification('Error al cargar productos', 'error');
        
        // MODO TEMPORAL: Si no hay API, usar datos locales
        usarDatosTemporales();
    }
}

async function cargarCategorias() {
    try {
        const response = await categoriasService.getAll();
        
        if (response.success) {
            categorias = response.data;
        }
    } catch (error) {
        console.error('Error al cargar categorías:', error);
        // Datos temporales
        categorias = [
            { id: 1, nombre: 'Electrónicos' },
            { id: 2, nombre: 'Accesorios' },
            { id: 3, nombre: 'Almacenamiento' }
        ];
    }
}

async function cargarPresentaciones() {
    try {
        const response = await presentacionesService.getAll();
        
        if (response.success) {
            presentaciones = response.data;
        }
    } catch (error) {
        console.error('Error al cargar presentaciones:', error);
        // Datos temporales
        presentaciones = [
            { id: 1, nombre: 'Unidad' },
            { id: 2, nombre: 'Caja' },
            { id: 3, nombre: 'Paquete' },
            { id: 4, nombre: 'Kit' }
        ];
    }
}

// =====================================================
// MODO TEMPORAL (Mientras no hay API)
// =====================================================
function usarDatosTemporales() {
    productos = [
        { 
            codigo_producto: 'PROD-001',
            nombre: 'Laptop HP', 
            cantidad: 15, 
            precio_venta: 2500000, 
            categoria_id: 1,
            presentacion_id: 4
        },
        { 
            codigo_producto: 'PROD-002',
            nombre: 'Mouse Logitech', 
            cantidad: 50, 
            precio_venta: 45000, 
            categoria_id: 2,
            presentacion_id: 3
        },
        { 
            codigo_producto: 'PROD-003',
            nombre: 'Teclado Mecánico', 
            cantidad: 30, 
            precio_venta: 180000, 
            categoria_id: 2,
            presentacion_id: 4
        },
        { 
            codigo_producto: 'PROD-004',
            nombre: 'Monitor Samsung 24"', 
            cantidad: 20, 
            precio_venta: 650000, 
            categoria_id: 1,
            presentacion_id: 1
        },
        { 
            codigo_producto: 'PROD-005',
            nombre: 'Webcam HD', 
            cantidad: 25, 
            precio_venta: 120000, 
            categoria_id: 2,
            presentacion_id: 2
        }
    ];
    
    renderTable(productos);
}

// =====================================================
// RENDERIZAR TABLA
// =====================================================
function renderTable(filteredProducts = productos) {
    const tbody = document.getElementById('tableBody');

    if (!filteredProducts || filteredProducts.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-state">
                    <div>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                            </path>
                        </svg>
                        <p>No se encontraron productos</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filteredProducts.map(p => {
        const categoria = obtenerNombreCategoria(p.categoria_id);
        const presentacion = obtenerNombrePresentacion(p.presentacion_id);
        
        return `
            <tr>
                <td><strong>${p.codigo_producto}</strong></td>
                <td>${p.nombre}</td>
                <td>${p.cantidad}</td>
                <td>${Helpers.formatCurrency(p.precio_venta)}</td>
                <td>${categoria}</td>
                <td>${presentacion}</td>
                <td>
                    <div class="actions">
                        <button class="btn-icon btn-warning" 
                                onclick="editProduct('${p.codigo_producto}')" 
                                title="Editar">✏️</button>
                        <button class="btn-icon btn-danger" 
                                onclick="deleteProduct('${p.codigo_producto}')" 
                                title="Eliminar">🗑️</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// =====================================================
// UTILIDADES
// =====================================================
function obtenerNombreCategoria(categoriaId) {
    const categoria = categorias.find(c => c.id === categoriaId);
    return categoria ? categoria.nombre : '-';
}

function obtenerNombrePresentacion(presentacionId) {
    const presentacion = presentaciones.find(p => p.id === presentacionId);
    return presentacion ? presentacion.nombre : '-';
}

// =====================================================
// CARGAR OPCIONES DE SELECT
// =====================================================
function cargarOpcionesSelect() {
    const categoriaSelect = document.getElementById('productCategory');
    const presentacionSelect = document.getElementById('productPresentation');

    if (!categoriaSelect || !presentacionSelect) return;

    // Limpiar y agregar opción por defecto
    categoriaSelect.innerHTML = '<option value="">Seleccione una categoría</option>';
    presentacionSelect.innerHTML = '<option value="">Seleccione una presentación</option>';

    // Cargar categorías
    categorias.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = cat.nombre;
        categoriaSelect.appendChild(option);
    });

    // Cargar presentaciones
    presentaciones.forEach(pre => {
        const option = document.createElement('option');
        option.value = pre.id;
        option.textContent = pre.nombre;
        presentacionSelect.appendChild(option);
    });
}

// =====================================================
// CONFIGURAR EVENTOS
// =====================================================
function configurarEventos() {
    // Búsqueda en tiempo real
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', buscarProductos);
    }

    // Formulario de producto
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('submit', guardarProducto);
    }

    // Cerrar modal al hacer clic fuera
    const modal = document.getElementById('productModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target.id === 'productModal') {
                closeModal();
            }
        });
    }
}

// =====================================================
// BÚSQUEDA
// =====================================================
function buscarProductos(e) {
    const searchTerm = e.target.value.toLowerCase().trim();
    
    if (!searchTerm) {
        renderTable(productos);
        return;
    }

    const filtered = productos.filter(p =>
        p.nombre.toLowerCase().includes(searchTerm) ||
        p.codigo_producto.toLowerCase().includes(searchTerm)
    );
    
    renderTable(filtered);
}

// =====================================================
// ABRIR MODAL
// =====================================================
function openModal(mode, productId = null) {
    const modal = document.getElementById('productModal');
    const form = document.getElementById('productForm');
    const title = document.getElementById('modalTitle');

    if (!modal || !form || !title) return;

    // Limpiar formulario
    form.reset();
    currentEditId = null;

    // Cargar opciones de select
    cargarOpcionesSelect();

    if (mode === 'edit' && productId) {
        // Modo edición
        const producto = productos.find(p => p.codigo_producto === productId);
        
        if (producto) {
            title.textContent = 'Editar Producto';
            
            document.getElementById('productCode').value = producto.codigo_producto;
            document.getElementById('productCode').readOnly = true;
            document.getElementById('productName').value = producto.nombre;
            document.getElementById('productQuantity').value = producto.cantidad;
            document.getElementById('productValue').value = producto.precio_venta;
            document.getElementById('productCategory').value = producto.categoria_id;
            document.getElementById('productPresentation').value = producto.presentacion_id;
            
            currentEditId = productId;
        }
    } else {
        // Modo agregar
        title.textContent = 'Agregar Producto';
        document.getElementById('productCode').readOnly = false;
        
        // Generar código automático
        const codigo = Helpers.generateUniqueCode('PROD');
        document.getElementById('productCode').value = codigo;
    }

    modal.classList.add('active');
}

// =====================================================
// CERRAR MODAL
// =====================================================
function closeModal() {
    const modal = document.getElementById('productModal');
    if (modal) {
        modal.classList.remove('active');
    }
    currentEditId = null;
}

// =====================================================
// GUARDAR PRODUCTO (CREAR O ACTUALIZAR)
// =====================================================
async function guardarProducto(e) {
    e.preventDefault();

    try {
        // Obtener valores del formulario
        const codigo = document.getElementById('productCode').value.trim();
        const nombre = document.getElementById('productName').value.trim();
        const cantidad = parseInt(document.getElementById('productQuantity').value);
        const precio = parseFloat(document.getElementById('productValue').value);
        const categoriaId = parseInt(document.getElementById('productCategory').value);
        const presentacionId = parseInt(document.getElementById('productPresentation').value);

        // Validaciones
        if (!Validators.isRequired(codigo)) {
            Helpers.showNotification('El código es obligatorio', 'error');
            return;
        }

        if (!Validators.isRequired(nombre)) {
            Helpers.showNotification('El nombre es obligatorio', 'error');
            return;
        }

        if (!Validators.isPositiveNumber(cantidad)) {
            Helpers.showNotification('La cantidad debe ser un número positivo', 'error');
            return;
        }

        if (!Validators.isPositiveNumber(precio)) {
            Helpers.showNotification('El precio debe ser un número positivo', 'error');
            return;
        }

        if (!categoriaId) {
            Helpers.showNotification('Debe seleccionar una categoría', 'error');
            return;
        }

        if (!presentacionId) {
            Helpers.showNotification('Debe seleccionar una presentación', 'error');
            return;
        }

        // Preparar datos
        const productoData = {
            codigo_producto: codigo,
            nombre: nombre,
            cantidad: cantidad,
            precio_venta: precio,
            categoria_id: categoriaId,
            presentacion_id: presentacionId
        };

        // Llamar al servicio
        let response;
        
        if (currentEditId) {
            // ACTUALIZAR
            response = await productosService.update(currentEditId, productoData);
            
            if (response.success) {
                // Actualizar en el array local
                const index = productos.findIndex(p => p.codigo_producto === currentEditId);
                if (index !== -1) {
                    productos[index] = { ...productos[index], ...productoData };
                }
                
                Helpers.showNotification('✅ Producto actualizado exitosamente', 'success');
            }
        } else {
            // CREAR
            response = await productosService.create(productoData);
            
            if (response.success) {
                // Agregar al array local
                productos.push(response.data);
                
                Helpers.showNotification('✅ Producto creado exitosamente', 'success');
            }
        }

        // Actualizar tabla
        renderTable();
        closeModal();

    } catch (error) {
        console.error('Error al guardar producto:', error);
        
        // MODO TEMPORAL: Si no hay API, simular guardado
        guardarProductoTemporal(e);
    }
}

// =====================================================
// GUARDAR PRODUCTO TEMPORAL (Mientras no hay API)
// =====================================================
function guardarProductoTemporal(e) {
    const codigo = document.getElementById('productCode').value.trim();
    const nombre = document.getElementById('productName').value.trim();
    const cantidad = parseInt(document.getElementById('productQuantity').value);
    const precio = parseFloat(document.getElementById('productValue').value);
    const categoriaId = parseInt(document.getElementById('productCategory').value);
    const presentacionId = parseInt(document.getElementById('productPresentation').value);

    const productoData = {
        codigo_producto: codigo,
        nombre: nombre,
        cantidad: cantidad,
        precio_venta: precio,
        categoria_id: categoriaId,
        presentacion_id: presentacionId
    };

    if (currentEditId) {
        // Actualizar
        const index = productos.findIndex(p => p.codigo_producto === currentEditId);
        if (index !== -1) {
            productos[index] = productoData;
            alert('✅ Producto actualizado exitosamente');
        }
    } else {
        // Agregar
        productos.push(productoData);
        alert('✅ Producto agregado exitosamente');
    }

    renderTable();
    closeModal();
}

// =====================================================
// EDITAR PRODUCTO
// =====================================================
function editProduct(codigo) {
    openModal('edit', codigo);
}

// =====================================================
// ELIMINAR PRODUCTO
// =====================================================
async function deleteProduct(codigo) {
    try {
        const producto = productos.find(p => p.codigo_producto === codigo);
        
        if (!producto) return;

        const confirmed = await Helpers.confirmAction(
            `¿Estás seguro de eliminar el producto "${producto.nombre}"?`
        );

        if (!confirmed) return;

        // Llamar al servicio
        const response = await productosService.delete(codigo);

        if (response.success) {
            // Eliminar del array local
            productos = productos.filter(p => p.codigo_producto !== codigo);
            
            renderTable();
            Helpers.showNotification('✅ Producto eliminado exitosamente', 'success');
        }

    } catch (error) {
        console.error('Error al eliminar producto:', error);
        
        // MODO TEMPORAL: Si no hay API, eliminar localmente
        if (confirm('¿Estás seguro de eliminar este producto?')) {
            productos = productos.filter(p => p.codigo_producto !== codigo);
            renderTable();
            alert('✅ Producto eliminado exitosamente');
        }
    }
}

// =====================================================
// EXPORTAR FUNCIONES GLOBALES (para onclick en HTML)
// =====================================================
window.openModal = openModal;
window.closeModal = closeModal;
window.editProduct = editProduct;
window.deleteProduct = deleteProduct;