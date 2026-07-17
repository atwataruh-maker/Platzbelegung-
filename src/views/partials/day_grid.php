<?php
/** @var array $grid expects keys: date, pitches, slots, cells */
/** @var bool $dragEnabled optional: Drag&Drop-Handles anzeigen (nur Admin-Wochenplan) */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
use App\Services\ScheduleGridService;

$dragEnabled = $dragEnabled ?? false;
$weekdayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];
?>
<div class="day-grid">
    <h3><?= View::e($weekdayNames[$grid['weekday']]) ?>, <?= View::e((new DateTime($grid['date']))->format('d.m.Y')) ?></h3>
    <?php if ($grid['pitches'] === []): ?>
        <p class="muted">Keine Plaetze fuer die aktuelle Saison hinterlegt.</p>
    <?php else: ?>
    <div class="table-scroll">
    <table class="grid-table<?= $dragEnabled ? ' grid-table-editable' : '' ?>" data-date="<?= View::e($grid['date']) ?>">
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
                    <td class="cell cell-<?= View::e($cell['type']) ?><?= !empty($cell['moved']) ? ' cell-moved' : '' ?>"
                        data-pitch-id="<?= (int) $pitch['id'] ?>"
                        data-slot-start="<?= View::e($slot['start']) ?>"
                        <?php if ($dragEnabled && $cell['type'] === 'training'): ?>
                            draggable="true"
                            data-base-schedule-id="<?= (int) $cell['base_schedule_id'] ?>"
                            data-duration-minutes="<?= (int) ($cell['duration_minutes'] ?? 30) ?>"
                        <?php endif; ?>
                        <?php if ($cell['type'] === 'training'): ?>
                            style="background-color: <?= ScheduleGridService::teamColor((int) $cell['team_id']) ?>;"
                        <?php endif; ?>
                    ><?= View::e($cell['label']) ?><?php
                        if (!empty($cell['displaced_team'])): ?><br><small class="displaced">verdr&auml;ngt: <?= View::e($cell['displaced_team']) ?><?php if (!empty($cell['moved_to'])): ?> &rarr; <?= View::e($cell['moved_to']) ?><?php endif; ?></small><?php
                        endif;
                        if (!empty($cell['moved_from'])): ?><br><small class="moved-badge">verschoben, sonst <?= View::e($cell['moved_from']) ?><?php if ($dragEnabled && !empty($cell['override_id'])): ?>
                            <form method="post" action="<?= View::e(Url::to('/admin/schedule-overrides/' . $cell['override_id'] . '/delete')) ?>" class="inline-form undo-form" onsubmit="return confirm('Verschiebung rueckgaengig machen?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="return_to" value="<?= View::e($_SERVER['REQUEST_URI'] ?? '/week') ?>">
                                <button type="submit" class="btn-link undo-link">&#8634; zurueck</button>
                            </form>
                        <?php endif; ?></small><?php
                        endif;
                        if (!empty($cell['moved_away_text'])): ?><small class="moved-away">&#8618; <?= View::e($cell['moved_away_text']) ?></small><?php
                        endif;
                    ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
