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

    $(document).Toasts('create', {
        position: 'bottomRight',
        class: 'bg-'+settings.type,
        title: null,
        close: false,
        subtitle: null,
        body: msg,
        delay : settings.delay
    })
}
