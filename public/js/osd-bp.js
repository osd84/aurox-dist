/**
 * Retourne le breakpoint Bootstrap courant
 *
 * asString = false → int
 * asString = true  → string
 *
 * 0 = xs   (< 576px)
 * 1 = sm   (≥ 576px)
 * 2 = md   (≥ 768px)
 * 3 = lg   (≥ 992px)
 * 4 = xl   (≥ 1200px)
 * 5 = xxl  (≥ 1400px)
 */
function bpGetCurrent(asString = false) {

    let value = 0;
    let label = 'xs';

    // xxl ≥ 1400px
    if (window.matchMedia('(min-width: 1400px)').matches) {
        value = 5;
        label = 'xxl';
    }
    // xl ≥ 1200px
    else if (window.matchMedia('(min-width: 1200px)').matches) {
        value = 4;
        label = 'xl';
    }
    // lg ≥ 992px
    else if (window.matchMedia('(min-width: 992px)').matches) {
        value = 3;
        label = 'lg';
    }
    // md ≥ 768px
    else if (window.matchMedia('(min-width: 768px)').matches) {
        value = 2;
        label = 'md';
    }
    // sm ≥ 576px
    else if (window.matchMedia('(min-width: 576px)').matches) {
        value = 1;
        label = 'sm';
    }

    return asString ? label : value;
}


/**
 * Traduit un breakpoint Bootstrap (string) en entier
 *
 * 'xs'  -> 0
 * 'sm'  -> 1
 * 'md'  -> 2
 * 'lg'  -> 3
 * 'xl'  -> 4
 * 'xxl' -> 5
 */
function bpTranslate(bp) {

    switch (bp) {
        case 'xxl':
            return 5;

        case 'xl':
            return 4;

        case 'lg':
            return 3;

        case 'md':
            return 2;

        case 'sm':
            return 1;

        case 'xs':
        default:
            return 0;
    }
}

