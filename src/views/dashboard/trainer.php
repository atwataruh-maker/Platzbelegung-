<?php
/** @var array $teams */
/** @var array $notifications */
use App\Core\Url;
use App\Core\View;
?>
<h1>Mein Dashboard</h1>

<section class="card">
    <h2>Meine Mannschaften</h2>
    <?php if ($teams === []): ?>
        <p class="muted">Dir ist noch keine Mannschaft zugeordnet. Bitte an den Admin wenden.</p>
    <?php else: ?>
        <ul class="plain-list">
        <?php foreach ($teams as $t): ?>
            <li><?= View::e($t['name']) ?> (<?= View::e($t['age_group'] ?? '') ?>)</li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <a class="btn-link" href="<?= View::e(Url::to('/my-team')) ?>">Zu meiner Mannschaftsansicht &rarr;</a>
</section>

<section class="card">
    <h2>Letzte Benachrichtigungen</h2>
    <?php if ($notifications === []): ?>
        <p class="muted">Keine Benachrichtigungen vorhanden.</p>
    <?php else: ?>
        <ul class="plain-list">
        <?php foreach ($notifications as $n): ?>
            <li>
                <strong><?= View::e($n['subject']) ?></strong>
                <span class="muted"> &ndash; <?= View::e((new DateTime($n['created_at']))->format('d.m.Y H:i')) ?></span>
                <p><?= nl2br(View::e($n['body'])) ?></p>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
