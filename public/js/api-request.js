
var api = {};
var apiLoaderPointer = null;

$('document').ready(initApiRequest);

function initApiRequest() {
    api = new ApiRequest();
}

class ApiRequest {
    constructor() {
        this.messageHandler = this._defaultMessageHandler;
    }

    // Gestion des erreurs AJAX
    handleError(message) {
        osdToastMessage("Impossible de traiter la requête.", "danger", 5);
        console.error("Erreur: ", message);
    }

    // Définir le CSRF Token
    setCsrfToken(csrfToken) {
        this.csrfToken = csrfToken;
    }

    // Effacer le CSRF Token
    clearCsrfToken() {
        this.csrfToken = null;
    }

    enableLoaderOnSucess() {
        this.hideLoaderOnSuccess = false;
    }

    disableLoaderOnSucess() {
        this.hideLoaderOnSuccess = true;
    }

    // Méthode GET : `url` obligatoire, `options` pour tout le reste
    async get(url, {
        success = null, fail = null,
        loaderMessage = "Traitement en cours...",
        loader = true,
        hideloader = true,
    } = {}) {
        if (loader) {
            apiLoaderPointer = loaderMessage.show(loaderMessage);
        }


        try {
            const response = await fetch(url, {
                method: "GET",
                headers: this._getHeaders(),
            });

            const data = await this._handleResponse(response);

            // Exécuter la callback appropriée si fournie
            if (data.status && typeof success === "function") {
                success(data); // Succès
            } else {
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            }

            if (!data.status && typeof fail === "function") {
                fail(data); // Échec
            }

            if (hideloader) {
                osdModalLoader.hide();
            }

            return data;
        } catch (error) {
            osdModalLoader.hide();
            this.handleError(error.message);
        }
    }

    // Méthode POST : `url` et `body` obligatoires, `options` pour tout le reste
    async post(url, body, {
        success = null, fail = null,
        loaderMessage = "Traitement en cours...",
        loader = false,
        hideloader = true,
        csrfToken = null
    } = {}) {

        if (loader) {
            osdModalLoader.show(loaderMessage);
        }

        try {
            const response = await fetch(url, {
                method: "POST",
                headers: this._getHeaders(csrfToken = csrfToken),
                body: JSON.stringify(body),
            });

            const data = await this._handleResponse(response);

            // Exécuter la callback appropriée si fournie
            if (data.status && typeof success === "function") {
                success(data); // Succès
            } else {
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            }

            if (!data.status && typeof fail === "function") {
                fail(data); // Échec
            }

            if (hideloader) {
                osdModalLoader.hide();
            }

            return data;
        } catch (error) {
            osdModalLoader.hide();
            this.handleError(error.message);
        }
    }

    // Génère les headers (avec ou sans CSRF)
    _getHeaders(csrfToken = null) {
        const headers = {
            "Content-Type": "application/json",
        };

        if (csrfToken) {
            headers["X-CSRFToken"] = csrfToken;
        }

        return headers;
    }

    // Gère la réponse HTTP
    async _handleResponse(response) {
        const data = await response.json();
        this.messageHandler(data); // Gère les messages via `this.toastApiMessages` ou autre
        if(data.validators) {
            await validatorForm(data.validators);
        }
        return data;
    }

    // Méthode par défaut pour gérer les messages (fallback si `this.toastApiMessages` n'est pas fourni)
    _defaultMessageHandler(data) {
        this.toastApiMessages(data, 5);
    }

    async toastApiMessages(response) {
        if (response.success) {
            // par courr tableau et afficher les messages
            response.success.forEach(message => {
                osdToastMessage(message, "success", 5);
            });
        }
        if (response.errors) {
            // par courr tableau et afficher les messages
            response.errors.forEach(message => {
                osdToastMessage(message, "danger", 5);
            });
        }
        if (response.infos) {
            // par courr tableau et afficher les messages
            response.infos.forEach(message => {
                osdToastMessage(message, "info", 5);
            });
        }
        if (response.warnings) {
            // par courr tableau et afficher les messages
            response.warnings.forEach(message => {
                osdToastMessage(message, "warning", 5);
            });
        }
    }

}
