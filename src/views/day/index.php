<?php
/** @var array $grid */
/** @var array $conflicts */
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
