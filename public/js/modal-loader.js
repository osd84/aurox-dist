/* global $ */
/* global console */
/* global window */

$('document').ready(modalHandlerInit);

function modalHandlerInit() {
    'use strict';

    // Attacher les listeners pour les actions
    $('body').delegate("#genericModalConfirmButton", 'click', handleGenericModalConfirm);
}

function initGenericModal(htmlContent) {
    /* Ajout et affichage d'une boîte de dialogue via un contenu HTML */
    'use strict';

    $('body').prepend(htmlContent);
    console.log('Opening generic modal...');
    $('#genericModal').modal();
}

function reloadCurrentPage() {
    /* Recharger la page active */
    'use strict';

    window.location.reload();
}

function closeGenericModal() {
    /* Fermer la boîte de dialogue modale */
    'use strict';

    $("#genericModalCloseButton").click();
}

function handleGenericModalConfirm() {
    /* Gestion de l'action pour le bouton "Confirmer" de la boîte de dialogue */
    'use strict';

    let actionCallback = $("#genericModalConfirmButton").data("callback");
    let callbackArguments = $("#genericModalConfirmButton").data("callback_params");

    if (actionCallback !== undefined) {
        if (actionCallback === "reload") {
            actionCallback = "reloadCurrentPage";
        }
        if (actionCallback === "close") {
            actionCallback = "closeGenericModal";
        }
    } else {
        actionCallback = "closeGenericModal";
    }

    $('#genericModalConfirmButton')
        .prepend("<span class='spinner-border spinner-border-sm' role='status' aria-hidden='true'></span>")
        .prop('disabled', true);

    if (callbackArguments === undefined) {
        eval(actionCallback)();
    } else {
        callbackArguments = callbackArguments.replace(/'/g, "\"");
        callbackArguments = callbackArguments.replace(/False/g, "false");
        callbackArguments = JSON.parse(callbackArguments);
        eval(actionCallback)(callbackArguments);
    }
}

function enableGenericModalConfirmButton() {
    /* Réactiver le bouton "Confirmer" */
    'use script';

    $('#genericModalConfirmButton>.spinner-border').remove();
    $('#genericModalConfirmButton').prop('disabled', false);
}

function handleGenericModalClose() {
    /* Supprimer la boîte de dialogue du DOM après sa fermeture */
    'use strict';

    setTimeout(function () {
        $("#genericModal").modal('dispose').remove();
    }, 500);
}

/******************** Gestionnaire de boîtes de confirmation et chargeurs ********************/

const
    modalCategories = ['default', 'light', 'dark', 'info', 'query', 'primary', 'secondary', 'success', 'warning', 'danger'];

const confirmationModalHTML = "<div class=\"modal\" id=\"confirmationDialog\" data-backdrop=\"static\" data-keyboard=\"false\" tabindex=\"-1\" aria-labelledby=\"staticBackdropLabel\" aria-hidden=\"true\">" +
    "<div class=\"modal-dialog modal-lg\">" +
    "<div class=\"modal-content\">" +
    "<div class=\"modal-header\">" +
    "<h5 class=\"modal-title\" id=\"confirmationDialogTitle\">Confirmation</h5>" +
    "<button id=\"confirmationDialogCloseButton\" type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">" +
    "<span aria-hidden=\"true\">&times;</span>" +
    "</button>" +
    "</div>" +
    "<div class=\"modal-body\">" +
    "<span id=\"confirmationDialogIcon\" class=\"\"></span>" +
    "<div id=\"confirmationDialogContent\" class=\"w-100\"></div>" +
    "</div>" +
    "<div class=\"modal-footer\">" +
    "<button id=\"confirmationDialogCancelButton\" type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">Cancel</button>" +
    "<button id=\"confirmationDialogConfirmButton\" type=\"button\" class=\"btn btn-primary\">Ok</button>" +
    "</div>" +
    "</div>" +
    "</div>" +
    "</div>";

const loaderModalHTML = "<div class=\"modal primary\" id=\"loaderDialog\" data-backdrop=\"static\" data-keyboard=\"false\" tabindex=\"-1\" aria-labelledby=\"staticBackdropLabel\" aria-hidden=\"true\">" +
    "<div class=\"modal-dialog modal-sm\">" +
    "<div class=\"modal-content\">" +
    "<div class=\"modal-header\">" +
    "<h5 class=\"modal-title\" id=\"loaderDialogTitle\">[title]</h5>" +
    "</div>" +
    "<div class=\"modal-body\">" +
    "<div style=\"flex-grow: 1; text-align: center;\">" +
    "<div class=\"spinner-border text-primary\" style=\"margin: auto\"></div>" +
    "</div>" +
    "</div>" +
    "</div>" +
    "</div>" +
    "</div>";

const modalManager = {
    confirm: async (title, message, category, options) => createGenericDialog(title, message, category, 'confirm', options),
    alert: async (title, message, category, options) => createGenericDialog(title, message, category, 'alert', options),
    showLoader: (title) => createLoaderModal(title),
    hideLoader: () => destroyLoaderModal(),
    defaultOptions: {
        cancelLabel: 'Cancel',
        confirmLabel: 'Ok'
    }
};

const createGenericDialog = (title, message, category, type, options) => {
    options = options || modalManager.defaultOptions;

    if (!modalCategories.includes(category)) {
        category = 'query';
    }

    let dialogTypeClass = (category === 'query') ? 'info' : category;
    let dialogButtonClass = `btn btn-${dialogTypeClass}`;
    let dialogIconClass = '';

    switch (category) {
        case 'info':
            dialogIconClass = 'fa-info-circle';
            break;
        case 'success':
            dialogIconClass = 'fa-check-circle';
            break;
        case 'warning':
            dialogIconClass = 'fa-exclamation-triangle';
            break;
        case 'danger':
            dialogIconClass = 'fa-times-circle';
            break;
        default:
            dialogIconClass = 'fa-question-circle';
    }

    return new Promise((resolve, reject) => {
        if ($("#confirmationDialog").length === 0) {
            $('body').prepend(confirmationModalHTML);
        }

        $("#confirmationDialog").removeClass().addClass(`modal fade ${dialogTypeClass}`);
        $("#confirmationDialogTitle").text(title);
        $("#confirmationDialogContent").html(message);
        $("#confirmationDialogIcon").removeClass().addClass(`fa ${dialogIconClass}`);
        $("#confirmationDialogConfirmButton").removeClass().addClass(dialogButtonClass);

        if (type === 'alert') {
            $("#confirmationDialogCancelButton").hide();
        } else {
            $("#confirmationDialogCancelButton").show();
        }

        // Gestion des actions sur les boutons
        $("#confirmationDialogCancelButton").off('click');
        $("#confirmationDialogConfirmButton").off('click');
        $("#confirmationDialogCancelButton").on('click', () => { $("#confirmationDialogCloseButton").click(); resolve(false); });
        $("#confirmationDialogConfirmButton").on('click', () => { $("#confirmationDialogCloseButton").click(); resolve(true); });

        $("#confirmationDialogCancelButton").text(options.cancelLabel);
        $("#confirmationDialogConfirmButton").text(options.confirmLabel);

        $("#confirmationDialog").modal();
    });
};

const createLoaderModal = (title) => {
    'use strict';

    if ($('#loaderDialog').length === 0) {
        title = title || "Loading...";
        let finalLoaderHtml = loaderModalHTML.replace('[title]', title);
        $('body').prepend(finalLoaderHtml);
        setTimeout(() => { $('#loaderDialog').modal(); }, 500);
    }
};

const destroyLoaderModal = () => {
    'use strict';
    if ($('#loaderDialog').length !== 0) {
        $('#loaderDialog').modal('hide');
        $('#loaderDialog').remove();
    }
};