<?php
/** @var array $grid */
/** @var string $prevDate */
/** @var string $nextDate */
use App\Core\Url;
use App\Core\View;
?>
<h1>Platzbelegung &ndash; oeffentliche Tagesansicht</h1>

<div class="week-nav">
    <a class="btn-link" href="<?= View::e(Url::to('/public/day/' . $prevDate)) ?>">&larr; Vorheriger Tag</a>
    <strong><?= View::e((new DateTime($grid['date']))->format('d.m.Y')) ?></strong>
    <a class="btn-link" href="<?= View::e(Url::to('/public/day/' . $nextDate)) ?>">Naechster Tag &rarr;</a>
</div>

<?php require __DIR__ . '/../partials/day_grid.php'; ?>
