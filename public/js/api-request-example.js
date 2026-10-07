

// gestion du csrf token
api.clearCsrfToken();
api.setCsrfToken("nouveau_token_csrf");

// Désactiver le loader en cas de succès
api.enableLoaderOnSucess();
api.disableLoaderOnSucess();

// Pour un datas.result == 1
function onSuccess(data) {
    console.log("Succès :", data);
}

// Pour un datas.result == 0
function onFailure(data) {
    console.error("Échec :", data.message);
}

// GET
api.get("/api/get", {
    loaderMessage: "Chargement en cours...",
    success: onSuccess,
    fail: onFailure,
} );


// POST
const body = { key: "valeur" };
api.post("/api/post", body, {
    loaderMessage: "Traitement en cours...",
    success: onSuccess,
    fail: onFailure,
});

// POST ex 2
function ajaxExempleSend() {

    let url = '/planning_model/save';
    let datas = {};

    datas.id_planning_model = id_planning_model;
    datas.archive = $("#archive").prop('checked');
    datas.name = $("#name").val();
    datas.id_station = $("#id_station").val();
    if (!cf.validateRequiredField($('#name'))) {
        return;
    }

    datas.sample_types = $("#sample_types").val();
    if (!cf.validateFieldWithFunction($('#sample_types'),
        function (val) {
            return val.length > 0
        },
        'Veuillez choisir un type d′échantillon')) {
        return;
    }

    datas.mr_pos = $("#mr_pos").prop('checked');
    datas.nb_sample = $("#nb_sample").val();
    if (!cf.validateFieldWithFunction($('#nb_sample'),
        function (val) {
            return val >= 0
        },
        'Ne doit pas être négatif')) {
        return;
    }

    api.post(url, datas, {
        loaderMessage: 'Sauvegarde du modèle de planning...',
        success: (data) => {
            if (data.datas.mode === 'create') {
                window.location.href = data.datas.edit_url;
            }
        }
    });

}
