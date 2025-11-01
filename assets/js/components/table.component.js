class TableComponent {
    constructor(tableBodyId, options = {}) {
        this.tbody = document.getElementById(tableBodyId);
        this.options = {
            emptyMessage: 'No hay datos para mostrar',
            ...options
        };
    }

    render(data, columns) {
        if (!data || data.length === 0) {
            this.renderEmpty(columns.length);
            return;
        }

        const rows = data.map(item => this.renderRow(item, columns)).join('');
        this.tbody.innerHTML = rows;
    }

    renderRow(item, columns) {
        const cells = columns.map(col => {
            const value = col.render ? col.render(item) : item[col.field];
            return `<td>${value}</td>`;
        }).join('');

        return `<tr>${cells}</tr>`;
    }

    renderEmpty(colspan) {
        this.tbody.innerHTML = `
            <tr>
                <td colspan="${colspan}" class="empty-state">
                    <p>${this.options.emptyMessage}</p>
                </td>
            </tr>
        `;
    }

    clear() {
        this.tbody.innerHTML = '';
    }
}
