<?php
/** @var array $days */
/** @var int $offset */
/** @var string $weekLabel */
/** @var array $openConflicts */
use App\Core\Auth;
use App\Core\Url;
use App\Core\View;
?>
<h1>Wochenansicht</h1>

<div class="week-nav">
    <a class="btn-link" href="<?= View::e(Url::to('/week/' . ($offset - 1))) ?>">&larr; Vorherige Woche</a>
    <strong><?= View::e($weekLabel) ?></strong>
    <a class="btn-link" href="<?= View::e(Url::to('/week/' . ($offset + 1))) ?>">Naechste Woche &rarr;</a>
</div>

<?php if ($openConflicts !== [] && Auth::isAdmin()): ?>
<section class="card conflict-summary">
    <strong><?= count($openConflicts) ?></strong> offene Konflikte &ndash;
    <a href="<?= View::e(Url::to('/admin/conflicts')) ?>">ansehen</a>
</section>
<?php endif; ?>

<?php foreach ($days as $grid): ?>
    <?php require __DIR__ . '/../partials/day_grid.php'; ?>
<?php endforeach; ?>
