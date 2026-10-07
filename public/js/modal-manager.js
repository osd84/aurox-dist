const modalManager = (function () {
    let modalElement = null;
    let loaderElement = null;

    const initModal = () => {
        if (!modalElement) {
            modalElement = document.createElement('div');
            modalElement.classList.add('modal', 'fade');
            modalElement.setAttribute('tabindex', '-1');
            modalElement.setAttribute('aria-hidden', 'true');

            modalElement.innerHTML = `
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalTitle">Chargement...</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div id="loader" class="d-flex justify-content-center align-items-center">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Chargement...</span>
                </div>
                <span id="message" class="ms-2">Veuillez patienter...</span>
              </div>
            </div>
          </div>
        </div>
      `;

            document.body.appendChild(modalElement);
        }

        loaderElement = modalElement.querySelector('#loader');
    };

    const showLoader = ($message = 'Veuillez patienter...', type = 'info', title = '') => {
        initModal();
        const messageElement = modalElement.querySelector('#message');
        const titleElement = modalElement.querySelector('#modalTitle');
        const headerElement = modalElement.querySelector('.modal-header');


        // Modifier le message et le type de la modal
        messageElement.textContent = $message;
        titleElement.textContent = title;

        // Ajouter une classe en fonction du type
        headerElement.classList.remove('bg-danger', 'bg-info', 'bg-warning', 'bg-success');
        headerElement.classList.add(`bg-${type}`);


        // Afficher la modal avec le loader
        $(modalElement).modal('show');
    };

    const hideLoader = () => {
        // Cacher la modal
        $(modalElement).modal('hide');
    };

    return {
        showLoader,
        hideLoader
    };
})();
