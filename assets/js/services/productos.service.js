class ProductosService {
    constructor() {
        this.api = apiService;
    }

    async getAll(params = {}) {
        return this.api.get(API_CONFIG.ENDPOINTS.PRODUCTOS, params);
    }

    async getById(codigo) {
        const endpoint = API_CONFIG.ENDPOINTS.PRODUCTO_BY_ID.replace(':id', codigo);
        return this.api.get(endpoint);
    }

    async create(producto) {
        return this.api.post(API_CONFIG.ENDPOINTS.PRODUCTOS, producto);
    }

    async update(codigo, producto) {
        const endpoint = API_CONFIG.ENDPOINTS.PRODUCTO_BY_ID.replace(':id', codigo);
        return this.api.put(endpoint, producto);
    }

    async delete(codigo) {
        const endpoint = API_CONFIG.ENDPOINTS.PRODUCTO_BY_ID.replace(':id', codigo);
        return this.api.delete(endpoint);
    }

    async search(query) {
        return this.api.get(API_CONFIG.ENDPOINTS.PRODUCTOS, { search: query });
    }

    async getByCategoria(categoriaId) {
        return this.api.get(API_CONFIG.ENDPOINTS.PRODUCTOS, { categoria_id: categoriaId });
    }

    async getBajoStock() {
        return this.api.get(API_CONFIG.ENDPOINTS.PRODUCTOS, { bajo_stock: true });
    }
}

const productosService = new ProductosService();
