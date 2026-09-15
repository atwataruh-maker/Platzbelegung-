<?php
/** @var array $entries */
use App\Core\View;
?>
<h1>Aenderungsprotokoll</h1>
<p class="muted">Zeigt die letzten 200 Aenderungen, inklusive automatischer Vorgaenge (Import, Konflikterkennung, Platzsperren).</p>

<section class="card">
    <table class="table">
        <thead><tr><th>Zeitpunkt</th><th>Benutzer</th><th>Bereich</th><th>Aktion</th><th>Details</th></tr></thead>
        <tbody>
        <?php foreach ($entries as $e): ?>
            <tr>
                <td><?= View::e((new DateTime($e['created_at']))->format('d.m.Y H:i')) ?></td>
                <td><?= View::e($e['user_name'] ?? 'System') ?></td>
                <td><?= View::e($e['entity_type']) ?> #<?= (int) $e['entity_id'] ?></td>
                <td><?= View::e($e['action']) ?></td>
                <td><?= View::e($e['description'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
