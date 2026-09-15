<?php
/** @var array|null $credential */
/** @var string|null $updatedByName */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<h1>DFBnet-Zugang</h1>

<section class="card">
    <p class="muted">
        Diese Zugangsdaten werden fuer den automatischen Spielplan-Import
        aus DFBnet verwendet (taegliche GitHub Action, siehe
        <code>automation/dfbnet-import</code>). Das Passwort wird
        verschluesselt gespeichert und nirgends im Klartext angezeigt.
        Der Import selbst laeuft ausserhalb dieser App; die Automatisierung
        ruft die Zugangsdaten ueber einen tokengesicherten Endpunkt ab.
    </p>

    <?php if ($credential !== null): ?>
        <p>
            Aktuell hinterlegt fuer Benutzername
            <strong><?= View::e($credential['username']) ?></strong>,
            zuletzt aktualisiert am
            <?= View::e((new DateTime($credential['updated_at']))->format('d.m.Y H:i')) ?> Uhr<?php if ($updatedByName !== null): ?>
                von <?= View::e($updatedByName) ?>
            <?php endif; ?>.
        </p>
    <?php else: ?>
        <p class="muted">Es sind noch keine DFBnet-Zugangsdaten hinterlegt.</p>
    <?php endif; ?>

    <form method="post" action="<?= View::e(Url::to('/admin/dfbnet')) ?>" class="stacked-form">
        <?= Csrf::field() ?>
        <label>DFBnet-Benutzername
            <input type="text" name="dfbnet_username" autocomplete="off" required>
        </label>
        <label>DFBnet-Passwort
            <input type="password" name="dfbnet_password" autocomplete="new-password" required>
        </label>
        <button type="submit" class="btn btn-primary">Speichern</button>
    </form>
</section>
