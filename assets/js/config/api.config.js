const API_CONFIG = {
    BASE_URL: 'http://localhost/stocky/api',
    ENDPOINTS: {
        // Autenticación
        LOGIN: '/auth/login.php',
        LOGOUT: '/auth/logout.php',
        VERIFY_SESSION: '/auth/verify.php',
        
        // Productos
        PRODUCTOS: '/productos',
        PRODUCTO_BY_ID: '/productos/:id',
        
        // Categorías
        CATEGORIAS: '/categorias',
        CATEGORIA_BY_ID: '/categorias/:id',
        
        // Presentaciones
        PRESENTACIONES: '/presentaciones',
        PRESENTACION_BY_ID: '/presentaciones/:id',
        
        // Proveedores
        PROVEEDORES: '/proveedores',
        PROVEEDOR_BY_ID: '/proveedores/:id',
        PROVEEDOR_PRODUCTOS: '/proveedores/:id/productos',
        
        // Clientes
        CLIENTES: '/clientes',
        CLIENTE_BY_ID: '/clientes/:id',
        CLIENTE_HISTORIAL: '/clientes/:id/historial',
        
        // Usuarios
        USUARIOS: '/usuarios',
        USUARIO_BY_ID: '/usuarios/:id',
        
        // Métodos de pago
        METODOS_PAGO: '/metodos-pago',
        METODO_PAGO_BY_ID: '/metodos-pago/:id',
        
        // Facturas
        FACTURAS: '/facturas',
        FACTURA_BY_ID: '/facturas/:id',
        GENERAR_FACTURA: '/facturas/generar',
        FACTURAS_ESTADISTICAS: '/facturas/estadisticas',
        
        // Dashboard
        DASHBOARD_STATS: '/dashboard/estadisticas'
    },
    HEADERS: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    TIMEOUT: 30000 // 30 segundos
};