<?php
/** @var array $conflicts */
/** @var array $suggestions */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<h1>Konflikte</h1>

<form method="post" action="<?= View::e(Url::to('/admin/conflicts/rescan')) ?>" class="inline-form">
    <?= Csrf::field() ?>
    <button type="submit" class="btn btn-secondary">Jetzt neu berechnen</button>
</form>

<?php if ($conflicts === []): ?>
    <p class="muted">Keine offenen Konflikte. Alles im gruenen Bereich.</p>
<?php endif; ?>

<?php foreach ($conflicts as $c): ?>
    <section class="card conflict-card conflict-<?= View::e($c['severity']) ?>">
        <h2>
            <span class="badge badge-<?= View::e($c['severity']) ?>"><?= View::e(strtoupper($c['severity'])) ?></span>
            <?= View::e($c['conflict_type']) ?> &ndash; <?= View::e($c['occurrence_date']) ?>
        </h2>
        <p><?= View::e($c['description']) ?></p>
        <p class="muted">
            Platz: <?= View::e($c['pitch_name'] ?? '-') ?> &middot;
            <?= View::e($c['start_datetime']) ?> bis <?= View::e($c['end_datetime']) ?>
        </p>

        <?php if (!empty($suggestions[$c['id']])): ?>
            <h3>Ausweichvorschlaege</h3>
            <ul class="plain-list">
                <?php foreach ($suggestions[$c['id']] as $s): ?>
                    <li>
                        <?= View::e($s['pitch_name']) ?>,
                        <?= View::e(substr($s['suggested_start'], 11, 5)) ?>&ndash;<?= View::e(substr($s['suggested_end'], 11, 5)) ?> Uhr
                        &ndash; Status: <?= View::e($s['status']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="actions">
            <form method="post" action="<?= View::e(Url::to('/admin/conflicts/' . $c['id'] . '/resolve')) ?>" class="inline-form">
                <?= Csrf::field() ?>
                <input type="text" name="note" placeholder="Begruendung (optional)">
                <button type="submit" class="btn btn-secondary">Als geloest markieren</button>
            </form>
            <form method="post" action="<?= View::e(Url::to('/admin/conflicts/' . $c['id'] . '/ignore')) ?>" class="inline-form">
                <?= Csrf::field() ?>
                <button type="submit" class="btn-link">Ignorieren</button>
            </form>
        </div>
    </section>
<?php endforeach; ?>
