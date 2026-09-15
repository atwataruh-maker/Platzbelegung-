<?php
/** @var array $grid */
/** @var string $prevDate */
/** @var string $nextDate */
/** @var array $homeGames */
/** @var string $weekLabel */
use App\Core\Url;
use App\Core\View;

$weekdayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];
?>
<h1>Platzbelegung &ndash; oeffentliche Tagesansicht</h1>

<div class="week-nav">
    <a class="btn-link" href="<?= View::e(Url::to('/public/day/' . $prevDate)) ?>">&larr; Vorheriger Tag</a>
    <strong><?= View::e((new DateTime($grid['date']))->format('d.m.Y')) ?></strong>
    <a class="btn-link" href="<?= View::e(Url::to('/public/day/' . $nextDate)) ?>">Naechster Tag &rarr;</a>
</div>

<?php if ($homeGames !== []): ?>
<section class="card">
    <h2>Heimspiele diese Woche (<?= View::e($weekLabel) ?>)</h2>
    <ul class="plain-list">
        <?php foreach ($homeGames as $g): ?>
            <?php $kickoff = new DateTime($g['kickoff_datetime']); ?>
            <li>
                <strong><?= View::e($g['team_name']) ?></strong> vs. <?= View::e($g['opponent']) ?>
                &ndash; <?= View::e($weekdayNames[(int) $kickoff->format('N')]) ?>,
                <?= View::e($kickoff->format('d.m.Y H:i')) ?> Uhr<?php if (!empty($g['pitch_name'])): ?>, <?= View::e($g['pitch_name']) ?><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../partials/day_grid.php'; ?>
