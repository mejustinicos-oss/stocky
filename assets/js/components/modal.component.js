class ModalComponent {
    constructor(modalId) {
        this.modal = document.getElementById(modalId);
        this.initEvents();
    }

    initEvents() {
        // Cerrar al hacer clic fuera
        this.modal?.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        // Cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });
    }

    open() {
        this.modal?.classList.add('active');
    }

    close() {
        this.modal?.classList.remove('active');
    }

    isOpen() {
        return this.modal?.classList.contains('active');
    }
}
