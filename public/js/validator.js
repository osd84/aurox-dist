async function validatorForm(messages) {
    // supprimer les anciens messages
    $('.validation-message').remove();

    // Assurez-vous que jQuery est chargé
    if (typeof jQuery === 'undefined') {
        console.error("jQuery est requis pour ValidatorForm");
        return;
    }

    // Boucle à travers chaque message de validation
    let is_valid = (messages.length === 0);
    messages.forEach(function(message) {
        // Récupérer les données du message
        const field = message.field;
        const text = message.msg;
        const type = message.type || 'danger'; // Par défaut, "danger"

        // Vérifier si le champ existe dans le DOM
        const inputField = $('#' + field);
        if (!inputField.length) {
            console.warn("Le champ avec l'ID", field, "n'existe pas.");
            return;
        }

        // Supprimer les anciens messages sous le champ pour éviter les doublons
        inputField.next('.validation-message').remove();

        // Créer le message d'erreur
        const validationMessage = $('<div>')
            .addClass('validation-message') // Classe dédiée pour le style
            .text(text);

        // Appliquer une couleur en fonction du type
        switch (type.toLowerCase()) {
            case 'warning':
                validationMessage.css('color', 'orange');
                break;
            case 'info':
                validationMessage.css('color', 'blue');
                break;
            case 'success':
                validationMessage.css('color', 'green');
                break;
            case 'danger':
            default:
                validationMessage.css('color', 'red');
                break;
        }

        // Ajouter le message d'erreur juste après le champ
        inputField.after(validationMessage);
    });
    return is_valid;
}