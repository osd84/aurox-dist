

function osd_slug(inputvar) {
    return inputvar
        .toLowerCase()
        .normalize('NFD')                 // sépare accents
        .replace(/[\u0300-\u036f]/g, '')  // supprime accents
        .replace(/[^a-z0-9]+/g, '-')      // remplace par -
        .replace(/^-+|-+$/g, '');         // trim -
}