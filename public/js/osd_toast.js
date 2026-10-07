function osdToastMessage(msg, type, delay, options = {
    title :  null,
    no_icon : false
}) {
    let settings = {
        message: msg || 'This is a toast message!',
        type: type || 'info', // 'info', 'success', 'warning', 'danger'
        delay: delay * 1000 || 3000
    };
    settings.title = options.title;

    // icon with emoji
    let icon = '';
    settings.type === 'info' ? icon = 'ℹ️' : '';
    settings.type === 'success' ? icon = '✅' : '';
    settings.type === 'warning' ? icon = '⚠️' : '';
    settings.type === 'danger' ? icon = '⚠️' : '';
    if (options.no_icon) {
        icon = '';
    }



    let toastHtml = `
            <div class="toast text-bg-${settings.type}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="${settings.delay}">
            ${settings.title ? 
                `<div class="toast-header">
                    <strong class="me-auto">${settings.title}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>` : ''}
                <div class="toast-body">
                  ${icon}  ${settings.message}
                </div>
            </div>
        `;

    let $toast = $(toastHtml);
    $('#toast-container').append($toast);
    let osdtoast = new bootstrap.Toast($toast[0]);
    osdtoast.show();
    return this;
}
