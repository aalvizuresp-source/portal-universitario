/* LabSeguridad — JavaScript nativo (local, sin dependencias).
   Confirmaciones de borrado y pequeñas ayudas de interfaz. */
(function () {
    'use strict';

    // Confirmación en cualquier elemento con data-confirmar.
    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (form.matches('[data-confirmar]')) {
            var msg = form.getAttribute('data-confirmar') || '¿Confirma la operación?';
            if (!window.confirm(msg)) {
                ev.preventDefault();
            }
        }
    });

    // Auto-ocultar alertas después de 6 segundos.
    setTimeout(function () {
        document.querySelectorAll('.alerta').forEach(function (a) {
            a.style.transition = 'opacity .5s';
            a.style.opacity = '0';
            setTimeout(function () { a.remove(); }, 500);
        });
    }, 6000);
})();
