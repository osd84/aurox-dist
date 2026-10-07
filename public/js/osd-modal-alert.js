// Gestionnaire de modales Bootstrap 5
class OsdModalAlert {
    // Constructeur
    constructor() {
        // count in

        if(document.getElementById('osd-modal-alert')) {
            console.log('ModalManager déjà initialisé');
            return;
        }

        const modalHTML = `<div class="modal fade" id="osd-modal-alert" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="osd-modal-alert-title">Title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" 
                    id="osd-modal-alert-close"
                    ></button>
                  </div>
                  <div class="modal-body">
                    <p id="osd-modal-alert-message">Msg</p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="osd-modal-alert-confirm">confirmText</button>
                  </div>
                </div>
              </div>
            </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHTML);

        this.modalDom = document.getElementById("osd-modal-alert");
        this.modalBs = new bootstrap.Modal(this.modalDom);


    }

    // Crée une modale d'alerte
    alert(title, message, options = {}) {
        const btnClass = options.btnClass || 'btn-primary';
        const btnText = options.btnText || 'OK';

        document.getElementById(`osd-modal-alert-title`).textContent = title;
        document.getElementById(`osd-modal-alert-message`).textContent = message;


        document.getElementById(`osd-modal-alert-confirm`).classList.value = '';
        document.getElementById(`osd-modal-alert-confirm`).classList.add('btn');
        document.getElementById(`osd-modal-alert-confirm`).classList.add(btnClass);

        document.getElementById(`osd-modal-alert-confirm`).textContent = btnText;


        return new Promise((resolve) => {

            document.getElementById(`osd-modal-alert-confirm`).addEventListener('click', function() {
                osdModalAlert.modalBs.hide();
                resolve(false);
            });

            document.getElementById(`osd-modal-alert-confirm`).addEventListener('click', function() {
                osdModalAlert.modalBs.hide();
                resolve(true);
            });


            osdModalAlert.modalBs.show();
        });
    }

}

// Créer une instance globale
document.addEventListener('DOMContentLoaded', function() {
    // Créer une instance globale
    window.osdModalAlert= new OsdModalAlert();
});
