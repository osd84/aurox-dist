// Gestionnaire de modales Bootstrap 5
class OsdModalConfirm {
    // Constructeur
    constructor() {
        // count in

        if(document.getElementById('osd-modal-confirm')) {
            console.log('ModalManager déjà initialisé');
            return;
        }

        const modalHTML = `<div class="modal fade" id="osd-modal-confirm" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="osd-modal-confirm-title">Title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" 
                    id="osd-modal-confirm-close"
                    ></button>
                  </div>
                  <div class="modal-body">
                    <p id="osd-modal-confirm-message">Msg</p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="osd-modal-confirm-cancel" data-bs-dismiss="modal">cancelText</button>
                    <button type="button" class="btn btn-primary" id="osd-modal-confirm-confirm">confirmText</button>
                  </div>
                </div>
              </div>
            </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHTML);

        this.modalDom = document.getElementById("osd-modal-confirm");
        this.modalBs = new bootstrap.Modal(this.modalDom);
    }

    // Crée une modale de confirmation
    async confirm(title, message, options = {}) {
        const btnClass = options.btnClass || 'btn-primary';
        const confirmText = options.confirmText || 'Confirmer';
        const cancelText = options.cancelText || 'Annuler';

        document.getElementById(`osd-modal-confirm-title`).textContent = title;
        document.getElementById(`osd-modal-confirm-message`).textContent = message;

        document.getElementById(`osd-modal-confirm-confirm`).classList.value = '';
        document.getElementById(`osd-modal-confirm-confirm`).classList.add('btn');
        document.getElementById(`osd-modal-confirm-confirm`).classList.add(btnClass);
        document.getElementById(`osd-modal-confirm-confirm`).textContent = confirmText;

        document.getElementById(`osd-modal-confirm-cancel`).textContent = cancelText;

        return new Promise((resolve) => {

            document.getElementById(`osd-modal-confirm-confirm`).addEventListener('click', function() {
                osdModalConfirm.modalBs.hide();
                resolve(true);
            });

            document.getElementById(`osd-modal-confirm-cancel`).addEventListener('click', function() {
                osdModalConfirm.modalBs.hide();
                resolve(false);
            });
            document.getElementById(`osd-modal-confirm-close`).addEventListener('click', function() {
                osdModalConfirm.modalBs.hide();
                resolve(false);
            });

            osdModalConfirm.modalBs.show();
        });
    }

}

// Créer une instance globale
document.addEventListener('DOMContentLoaded', function() {
    // Créer une instance globale
    window.osdModalConfirm = new OsdModalConfirm();
});
