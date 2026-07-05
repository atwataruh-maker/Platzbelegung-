<?php
/** @var array $days */
/** @var int $offset */
/** @var string $weekLabel */
use App\Core\Url;
use App\Core\View;
?>
<h1>Platzbelegung &ndash; oeffentliche Wochenansicht</h1>

<div class="week-nav">
    <a class="btn-link" href="<?= View::e(Url::to('/public/week/' . ($offset - 1))) ?>">&larr; Vorherige Woche</a>
    <strong><?= View::e($weekLabel) ?></strong>
    <a class="btn-link" href="<?= View::e(Url::to('/public/week/' . ($offset + 1))) ?>">Naechste Woche &rarr;</a>
</div>

<?php foreach ($days as $grid): ?>
    <?php require __DIR__ . '/../partials/day_grid.php'; ?>
<?php endforeach; ?>
