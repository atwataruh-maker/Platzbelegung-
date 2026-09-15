<?php
/** @var array $grid expects keys: date, pitches, slots, cells */
/** @var bool $dragEnabled optional: Drag&Drop-Handles anzeigen (nur Admin-Wochenplan) */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
use App\Services\ScheduleGridService;

$dragEnabled = $dragEnabled ?? false;
$weekdayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];
$pitchCount = count($grid['pitches']);
$slotCount = count($grid['slots']);
$events = $pitchCount > 0 ? ScheduleGridService::buildEvents($grid) : [];
?>
<div class="day-grid">
    <h3><?= View::e($weekdayNames[$grid['weekday']]) ?>, <?= View::e((new DateTime($grid['date']))->format('d.m.Y')) ?></h3>
    <?php if ($pitchCount === 0): ?>
        <p class="muted">Keine Plaetze fuer die aktuelle Saison hinterlegt.</p>
    <?php else: ?>
    <div class="day-grid-scroll">
    <div class="day-grid-table<?= $dragEnabled ? ' day-grid-table-editable' : '' ?>"
         data-date="<?= View::e($grid['date']) ?>"
         style="grid-template-columns: 58px repeat(<?= $pitchCount ?>, 118px); grid-template-rows: 38px repeat(<?= $slotCount ?>, 30px);">

        <div class="grid-corner"></div>
        <?php foreach ($grid['pitches'] as $pIndex => $pitch): ?>
            <div class="grid-pitch-header" style="grid-column:<?= $pIndex + 2 ?>;grid-row:1;"><?= View::e($pitch['name']) ?></div>
        <?php endforeach; ?>

        <?php foreach ($grid['slots'] as $sIndex => $slot): ?>
            <div class="grid-time-label" style="grid-row:<?= $sIndex + 2 ?>;"><?= View::e($slot['start']) ?></div>
        <?php endforeach; ?>

        <?php foreach ($grid['pitches'] as $pIndex => $pitch): ?>
            <?php foreach ($grid['slots'] as $sIndex => $slot): ?>
                <div class="cell cell-drop" data-pitch-id="<?= (int) $pitch['id'] ?>" data-slot-start="<?= View::e($slot['start']) ?>"
                     style="grid-column:<?= $pIndex + 2 ?>;grid-row:<?= $sIndex + 2 ?>;"></div>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <?php foreach ($grid['pitches'] as $pIndex => $pitch): ?>
            <?php foreach ($events[$pitch['id']] ?? [] as $event): ?>
                <?php $eventStart = $grid['slots'][$event['start_index']]['start']; ?>
                <div class="cell cell-event cell-event-<?= View::e($event['type']) ?><?= !empty($event['moved']) ? ' cell-event-moved' : '' ?>"
                     data-pitch-id="<?= (int) $pitch['id'] ?>"
                     data-slot-start="<?= View::e($eventStart) ?>"
                     style="grid-column:<?= $pIndex + 2 ?>;grid-row:<?= $event['start_index'] + 2 ?> / span <?= $event['span'] ?>;<?php
                        if ($event['type'] === 'training'): ?>background-color:<?= ScheduleGridService::teamColor((int) $event['team_id']) ?>;<?php endif;
                     ?>"
                     <?php if ($dragEnabled && $event['type'] === 'training'): ?>
                        draggable="true"
                        data-base-schedule-id="<?= (int) $event['base_schedule_id'] ?>"
                        data-duration-minutes="<?= (int) ($event['duration_minutes'] ?? ($event['span'] * 30)) ?>"
                     <?php endif; ?>
                >
                    <?php if ($event['type'] === 'free' && !empty($event['moved_away_text'])): ?>
                        <div class="cell-event-label cell-event-ghost">&#8618; <?= View::e($event['moved_away_text']) ?></div>
                    <?php else: ?>
                        <div class="cell-event-label"><?= View::e($event['label']) ?></div>
                        <?php if (!empty($event['displaced_team'])): ?>
                            <div class="cell-event-note">verdr&auml;ngt: <?= View::e($event['displaced_team']) ?><?php if (!empty($event['moved_to'])): ?> &rarr; <?= View::e($event['moved_to']) ?><?php endif; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($event['moved_from'])): ?>
                            <div class="cell-event-note">
                                verschoben, sonst <?= View::e($event['moved_from']) ?>
                                <?php if ($dragEnabled && !empty($event['override_id'])): ?>
                                    <form method="post" action="<?= View::e(Url::to('/admin/schedule-overrides/' . $event['override_id'] . '/delete')) ?>" class="inline-form undo-form" onsubmit="return confirm('Verschiebung rueckgaengig machen?');">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="return_to" value="<?= View::e($_SERVER['REQUEST_URI'] ?? '/week') ?>">
                                        <button type="submit" class="btn-link undo-link">&#8634; zur&uuml;ck</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>

    </div>
    </div>
    <?php endif; ?>
</div>
