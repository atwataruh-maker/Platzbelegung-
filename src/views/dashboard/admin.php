<?php
/** @var array $openConflicts */
/** @var array $upcomingGames */
use App\Core\Url;
use App\Core\View;
?>
<h1>Admin-Dashboard</h1>

<section class="card">
    <h2>Offene Konflikte (<?= count($openConflicts) ?>)</h2>
    <?php if ($openConflicts === []): ?>
        <p class="muted">Keine offenen Konflikte.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Schwere</th><th>Typ</th><th>Datum</th><th>Platz</th><th>Beschreibung</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($openConflicts, 0, 10) as $c): ?>
                <tr>
                    <td><span class="badge badge-<?= View::e($c['severity']) ?>"><?= View::e($c['severity']) ?></span></td>
                    <td><?= View::e($c['conflict_type']) ?></td>
                    <td><?= View::e($c['occurrence_date']) ?></td>
                    <td><?= View::e($c['pitch_name'] ?? '-') ?></td>
                    <td><?= View::e($c['description']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <a class="btn-link" href="<?= View::e(Url::to('/admin/conflicts')) ?>">Alle Konflikte ansehen &rarr;</a>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Kommende Heimspiele (14 Tage)</h2>
    <?php if ($upcomingGames === []): ?>
        <p class="muted">Keine Heimspiele in den naechsten 14 Tagen.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Datum</th><th>Mannschaft</th><th>Gegner</th><th>Platz</th></tr></thead>
            <tbody>
            <?php foreach ($upcomingGames as $g): ?>
                <tr>
                    <td><?= View::e((new DateTime($g['kickoff_datetime']))->format('d.m.Y H:i')) ?></td>
                    <td><?= View::e($g['team_name']) ?></td>
                    <td><?= View::e($g['opponent']) ?></td>
                    <td><?= View::e($g['pitch_name'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <a class="btn-link" href="<?= View::e(Url::to('/admin/games')) ?>">Spiele verwalten &rarr;</a>
</section>
