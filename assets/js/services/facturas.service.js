class FacturasService {
    constructor() {
        this.api = apiService;
    }

    async getAll(params = {}) {
        return this.api.get(API_CONFIG.ENDPOINTS.FACTURAS, params);
    }

    async getById(numeroFactura) {
        const endpoint = API_CONFIG.ENDPOINTS.FACTURA_BY_ID.replace(':id', numeroFactura);
        return this.api.get(endpoint);
    }

    async generar(factura) {
        return this.api.post(API_CONFIG.ENDPOINTS.GENERAR_FACTURA, factura);
    }

    async getEstadisticas(fechaInicio, fechaFin) {
        return this.api.get(API_CONFIG.ENDPOINTS.FACTURAS_ESTADISTICAS, {
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin
        });
    }

    async generarNumeroFactura() {
        // Esto vendría del backend
        return this.api.get(API_CONFIG.ENDPOINTS.GENERAR_FACTURA + '/numero');
    }
}

const facturasService = new FacturasService();
