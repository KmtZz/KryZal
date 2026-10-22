<dialog class="trainer-dialog" id="trainer-dialog" aria-labelledby="trainer-title">
    <button type="button" class="dialog-close" data-trainer-close aria-label="Cerrar tarjeta">×</button>
    <div data-trainer-avatar class="trainer-large-avatar"></div>
    <span class="eyebrow">EQUIPO KRYZAL</span>
    <h2 id="trainer-title"></h2>
    <p data-trainer-specialty></p>
    <p class="ticket-context" data-trainer-class></p>
    <a class="button" data-trainer-contact>Contactar con el entrenador</a>
    <p class="hint" data-trainer-hint>
        <?php if ($me['role'] === 'usuario'): ?>
        Reserva una plaza para enviar una consulta sobre esta clase. Para otras dudas, <a
            href="?page=requests&new=1">contacta con Administración</a>.
        <?php else: ?>
        Puedes iniciar una conversación con el equipo desde <a href="?page=requests&new=1">Mensajes</a>.
        <?php endif; ?>
    </p>
</dialog>
