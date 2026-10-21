<script>
(function () {
    document.querySelectorAll('[data-medir]').forEach(function (campo) {
        var contenedor = campo.closest('div').parentElement;
        var medidor = contenedor.querySelector('[data-medidor]');
        var lista = contenedor.querySelector('[data-requisitos]');
        campo.addEventListener('input', function () {
            var v = campo.value;
            var reglas = { largo: v.length >= 8, mayus: /[A-Z]/.test(v), minus: /[a-z]/.test(v), num: /\d/.test(v), simbolo: /[^A-Za-z0-9]/.test(v) };
            var ok = Object.values(reglas).filter(Boolean).length;
            if (medidor) medidor.dataset.nivel = v ? Math.max(1, Math.min(4, ok - 1)) : 0;
            if (lista) Object.keys(reglas).forEach(function (k) {
                var li = lista.querySelector('[data-r="' + k + '"]');
                if (li) li.classList.toggle('ok', reglas[k]);
            });
        });
    });
})();
</script>
