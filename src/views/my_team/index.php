<?php
/** @var array $teamsData */
/** @var array $pendingConfirmations */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

$weekdayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];
?>
<h1>Meine Mannschaft</h1>

<?php if ($pendingConfirmations !== []): ?>
<section class="card conflict-summary">
    <h2>Ausweichvorschlaege &ndash; bitte bestaetigen</h2>
    <?php foreach ($pendingConfirmations as $pc): ?>
        <div class="confirmation-item">
            <p>
                Am <strong><?= View::e((new DateTime($pc['occurrence_date']))->format('d.m.Y')) ?></strong>
                Training auf <strong><?= View::e($pc['pitch_name']) ?></strong>,
                <?= View::e(substr($pc['suggested_start'], 11, 5)) ?>&ndash;<?= View::e(substr($pc['suggested_end'], 11, 5)) ?> Uhr.
            </p>
            <div class="actions">
                <form method="post" action="<?= View::e(Url::to('/my-team/confirm/' . $pc['id'])) ?>" class="inline-form">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-primary">Bestaetigen</button>
                </form>
                <form method="post" action="<?= View::e(Url::to('/my-team/decline/' . $pc['id'])) ?>" class="inline-form">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn-link">Ablehnen</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php foreach ($teamsData as $entry): ?>
    <section class="card">
        <h2><?= View::e($entry['team']['name']) ?></h2>

        <h3>Trainingszeiten</h3>
        <?php if ($entry['schedule'] === []): ?>
            <p class="muted">Keine Trainingszeiten hinterlegt.</p>
        <?php else: ?>
            <ul class="plain-list">
            <?php foreach ($entry['schedule'] as $s): ?>
                <li><?= View::e($weekdayNames[(int) $s['weekday']]) ?>, <?= View::e(substr($s['start_time'], 0, 5)) ?>&ndash;<?= View::e(substr($s['end_time'], 0, 5)) ?> Uhr auf <?= View::e($s['pitch_name']) ?></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h3>Naechste Spiele</h3>
        <?php if ($entry['games'] === []): ?>
            <p class="muted">Keine anstehenden Spiele.</p>
        <?php else: ?>
            <ul class="plain-list">
            <?php foreach ($entry['games'] as $g): ?>
                <li><?= View::e((new DateTime($g['kickoff_datetime']))->format('d.m.Y H:i')) ?> vs. <?= View::e($g['opponent']) ?> (<?= $g['is_home'] ? View::e($g['pitch_name'] ?? '-') : 'Auswaerts' ?>)</li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($entry['suggestions'] !== []): ?>
            <h3>Offene Ausweichvorschlaege</h3>
            <ul class="plain-list">
            <?php foreach ($entry['suggestions'] as $s): ?>
                <li>
                    <?= View::e((new DateTime($s['occurrence_date']))->format('d.m.Y')) ?>:
                    statt <?= View::e($s['original_pitch_name'] ?? '-') ?> &rarr; <?= View::e($s['pitch_name']) ?>
                    (<?= View::e($s['status']) ?>)
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
