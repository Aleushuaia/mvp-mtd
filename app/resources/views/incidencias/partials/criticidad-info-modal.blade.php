{{--
    Modal informativo de solo lectura: explica los niveles de criticidad.
    Compartido entre la vista de detalle del socio y la de gestión del operador.
--}}
<div class="modal fade" id="modalCriticidadInfo" tabindex="-1" aria-labelledby="modalCriticidadInfoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h6" id="modalCriticidadInfoTitulo">Niveles de criticidad</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <ul class="criticidad-info-lista">
                    <li>
                        <span class="badge crit-badge crit-normal">Normal</span>
                        <p class="mb-1"><strong>Criterio:</strong> molestia menor, sin riesgo; se atiende en la rutina.</p>
                        <p class="text-muted small mb-0"><strong>Ejemplo:</strong> una canilla que gotea en el vestuario.</p>
                    </li>
                    <li>
                        <span class="badge crit-badge crit-urgente">Urgente</span>
                        <p class="mb-1"><strong>Criterio:</strong> afecta a varios socios o un sector, o puede agravarse en el día.</p>
                        <p class="text-muted small mb-0"><strong>Ejemplo:</strong> pérdida de agua que anega el sector de piletones.</p>
                    </li>
                    <li>
                        <span class="badge crit-badge crit-muy-urgente">Muy Urgente</span>
                        <p class="mb-1"><strong>Criterio:</strong> riesgo para la salud o la seguridad, o daño grave inmediato.</p>
                        <p class="text-muted small mb-0"><strong>Ejemplo:</strong> cable eléctrico expuesto cerca de un piletón.</p>
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-trebol" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
