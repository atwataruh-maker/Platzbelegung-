<?php
/** @var array $grid expects keys: date, pitches, slots, cells */
use App\Core\View;
use App\Services\ScheduleGridService;

$weekdayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];
?>
<div class="day-grid">
    <h3><?= View::e($weekdayNames[$grid['weekday']]) ?>, <?= View::e((new DateTime($grid['date']))->format('d.m.Y')) ?></h3>
    <?php if ($grid['pitches'] === []): ?>
        <p class="muted">Keine Plaetze fuer die aktuelle Saison hinterlegt.</p>
    <?php else: ?>
    <div class="table-scroll">
    <table class="grid-table">
        <thead>
        <tr>
            <th class="time-col">Zeit</th>
            <?php foreach ($grid['pitches'] as $pitch): ?>
                <th><?= View::e($pitch['name']) ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($grid['slots'] as $slot): ?>
            <tr>
                <td class="time-col"><?= View::e($slot['start']) ?></td>
                <?php foreach ($grid['pitches'] as $pitch): ?>
                    <?php $cell = $grid['cells'][$pitch['id']][$slot['start']]; ?>
                    <td class="cell cell-<?= View::e($cell['type']) ?>"
                        <?php if ($cell['type'] === 'training'): ?>
                            style="background-color: <?= ScheduleGridService::teamColor((int) $cell['team_id']) ?>;"
                        <?php endif; ?>
                    ><?= View::e($cell['label']) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
