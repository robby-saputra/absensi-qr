const jurusanSelect = document.getElementById("jurusan");

const kelasSelect = document.getElementById("kelas_id");

const waliInput = document.getElementById("wali_kelas");

/*
    |--------------------------------------------------------------------------
    | FILTER KELAS BERDASARKAN JURUSAN
    |--------------------------------------------------------------------------
    */
jurusanSelect.addEventListener("change", function () {
    const jurusanId = this.value;

    for (let option of kelasSelect.options) {
        if (option.value === "") continue;

        if (option.dataset.jurusan === jurusanId) {
            option.style.display = "block";
        } else {
            option.style.display = "none";
        }
    }

    kelasSelect.value = "";
    waliInput.value = "";
});

/*
    |--------------------------------------------------------------------------
    | AUTO WALI KELAS
    |--------------------------------------------------------------------------
    */
kelasSelect.addEventListener("change", function () {
    const selected = this.options[this.selectedIndex];

    waliInput.value = selected.dataset.wali ?? "-";
});
