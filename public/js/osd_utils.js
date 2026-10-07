
/**
 * Converts a given text into a URL-friendly slug.
 * The method transforms the input text by converting it to lowercase, trimming whitespaces,
 * replacing spaces with hyphens, removing non-alphanumeric characters (excluding hyphens),
 * and collapsing consecutive hyphens into a single hyphen.
 *
 * @param {string} text - The input string to be converted into a slug.
 * @return {string} - A URL-friendly slug generated from the input text.
 */
function osdSlugify(text) {
    return text.toString().toLowerCase().trim()
        .normalize('NFD')                // Décompose les caractères accentués
        .replace(/[\u0300-\u036f]/g, '') // Supprime les diacritiques
        .replace(/\s+/g, '-')            // Replace spaces with -
        .replace(/[^\w-]+/g, '')         // Remove all non-word chars
        .replace(/--+/g, '-');           // Replace multiple - with single -
}

/**
 * Sanitizes the provided input by escaping potentially harmful characters
 * to prevent injection attacks such as XSS.
 *
 * @param {string|*} input - The input to be sanitized. Non-string values will be converted to strings.
 * @return {string} The sanitized string with special characters safely encoded.
 */
function sanitizeInput(input) {
    if (typeof input !== 'string') {
        input = String(input);
    }

    // Encode les caractères HTML dangereux
    return input
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;')
        .replace(/\//g, '&#x2F;');
}



/**
 * Displays an error message associated with a specific input field and adds validation styling.
 *
 * @param {string} fieldId - The ID of the input field where the error message should be displayed.
 * @param {string} message - The error message to display for the specified input field.
 * @return {void}
 */
function showError(fieldId, message) {
    let field = document.getElementById(fieldId);
    // search by name
    if(!field) field = document.getElementsByName(fieldId)[0];
    if(!field) return console.error(
        `showError: Field with ID "${fieldId}" not found. Please check the field ID or NAME and try again.`
    )
    field.classList.add('is-invalid');

    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error text-danger small mt-1';
    errorDiv.textContent = message;

    field.parentNode.appendChild(errorDiv);

    if (!field.dataset.errorListenerAdded) {
        field.addEventListener('input', function() {
            field.classList.remove('is-invalid');
            const error = field.parentNode.querySelector('.field-error');
            if (error) error.remove();
        });
        field.dataset.errorListenerAdded = 'true';
    }
}


function clearAllErrors() {
    const fields = $('.field-error').remove();
}