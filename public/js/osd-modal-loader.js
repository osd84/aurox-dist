/**
 * Gestionnaire simplifié de modales de chargement Bootstrap 5
 */



class OsdModalLoader {
    constructor() {

        // si la modal existe déjà on saute
        if(document.getElementById('osd-loader-modal')) {
            console.log('LoaderManager déjà initialisé');
            return;
        }

        this.counter = 0;

        const modalHTML = `
        <div class="modal fade" id="osd-loader-modal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Chargement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Veuillez patienter...</p>
              </div>
            </div>
          </div>
        </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        this.modalDom = document.getElementById("osd-loader-modal");
        this.modalBs = new bootstrap.Modal(this.modalDom,{
                backdrop: 'static',
                keyboard: false
            }
        );
    }

    /**
     * Crée et affiche une modale de chargement
     * @param {string} title - Titre de la modale (optionnel)
     * @param {string} message - Message à afficher (optionnel)
     * @param {boolean} showClose
     * @returns {Object} - Interface de contrôle du loader
     */
    show(title = 'Chargement', message = 'Veuillez patienter...', showClose = false) {

        if(title) {
            this.modalDom.querySelector('.modal-title').textContent = title;
        }
        if(message) {
            this.modalDom.querySelector('.modal-body p').textContent = message;
        }
        if(showClose) {
            this.modalDom.querySelector('.btn-close').style.display = 'block';
        }
        else {
            this.modalDom.querySelector('.btn-close').style.display = 'none';
        }
        this.modalBs.show();
    }

    hide() {
        this.modalBs.hide();
    }

    updateMsg(msg) {
        this.modalDom.querySelector('.modal-body p').textContent = msg;
    }

    updateTitle(title) {
        this.modalDom.querySelector('.modal-title').textContent = title;
    }
}

// Créer une instance globale
document.addEventListener('DOMContentLoaded', function() {
    // Créer une instance globale
    window.osdModalLoader = new OsdModalLoader();
});
