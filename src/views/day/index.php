<?php
/** @var array $grid */
/** @var array $conflicts */
/** @var array $moves */
/** @var string $prevDate */
/** @var string $nextDate */
use App\Core\Url;
use App\Core\View;
?>
<h1>Tagesansicht</h1>

<div class="week-nav">
    <a class="btn-link" href="<?= View::e(Url::to('/day/' . $prevDate)) ?>">&larr; Vorheriger Tag</a>
    <strong><?= View::e((new DateTime($grid['date']))->format('d.m.Y')) ?></strong>
    <a class="btn-link" href="<?= View::e(Url::to('/day/' . $nextDate)) ?>">Naechster Tag &rarr;</a>
</div>

<?php if ($moves !== []): ?>
<section class="card move-summary">
    <h2>Verschiebungen an diesem Tag</h2>
    <ul class="plain-list">
        <?php foreach ($moves as $m): ?>
            <li>
                <strong><?= View::e($m['team_name']) ?></strong>:
                <?= View::e($m['original_pitch_name']) ?> (<?= View::e(substr($m['original_start_time'], 0, 5)) ?>&ndash;<?= View::e(substr($m['original_end_time'], 0, 5)) ?>)
                &rarr; <?= View::e($m['new_pitch_name']) ?> (<?= View::e(substr($m['new_start_time'], 0, 5)) ?>&ndash;<?= View::e(substr($m['new_end_time'], 0, 5)) ?>)
                <?php if ($m['reason'] === 'game_conflict' && !empty($m['game_team_name'])): ?>
                    <span class="muted">&ndash; wegen Spiel <?= View::e($m['game_team_name']) ?><?= !empty($m['game_opponent']) ? ' vs. ' . View::e($m['game_opponent']) : '' ?></span>
                <?php elseif ($m['reason'] === 'manual'): ?>
                    <span class="muted">&ndash; manuell verschoben</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php if ($conflicts !== []): ?>
<section class="card conflict-summary">
    <h2>Konflikte an diesem Tag</h2>
    <ul class="plain-list">
        <?php foreach ($conflicts as $c): ?>
            <li>
                <span class="badge badge-<?= View::e($c['severity']) ?>"><?= View::e(strtoupper($c['severity'])) ?></span>
                <?= View::e($c['description']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../partials/day_grid.php'; ?>
