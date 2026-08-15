<?php
/**
 * Project: LMOnext
 * Filename: addon/liga-notizen/view_notizen.php
 * Fileversion: 1.0.0
 *
 * PHP version 8.2
 *
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * ── View: Liga-Notizen ──────────────────────────────────────────────────────
 *
 * Zeigt alle Notizen einer ausgewählten Liga an. Notizen können angelegt,
 * bearbeitet und gelöscht werden. Farbcodierung für visuelle Kategorisierung.
 */

// ── Tabelle sicherstellen ────────────────────────────────────────────────────
ensureNotizenTable();

// ── Liga-Auswahl ──────────────────────────────────────────────────────────────
$db = getDB();
$ligaId = (int)($_GET['liga_id'] ?? 0);

// Alle Ligen für Dropdown
$ligenStmt = $db->query('SELECT id, name FROM ' . tbl('liga') . ' ORDER BY name');
$allLigen = $ligenStmt ? $ligenStmt->fetchAll() : [];

// ── Notizen für ausgewählte Liga laden ────────────────────────────────────────
$notizen = [];
$selectedLiga = null;
if ($ligaId > 0) {
    $sLiga = $db->prepare('SELECT id, name FROM ' . tbl('liga') . ' WHERE id = ?');
    $sLiga->execute([$ligaId]);
    $selectedLiga = $sLiga->fetch();

    $sNotizen = $db->prepare(
        'SELECT * FROM ' . tbl('liga_notizen') . ' WHERE liga_id = ? ORDER BY geaendert_am DESC'
    );
    $sNotizen->execute([$ligaId]);
    $notizen = $sNotizen->fetchAll();
}

// ── Bearbeitungsmodus ────────────────────────────────────────────────────────
$editNotiz = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0) {
    $sEdit = $db->prepare('SELECT * FROM ' . tbl('liga_notizen') . ' WHERE id = ?');
    $sEdit->execute([$editId]);
    $editNotiz = $sEdit->fetch();
}

// ── Farb-Definitionen ───────────────────────────────────────────────────────
$farben = [
    'gelb'  => ['#fef3c7', '#92400e', '#f59e0b'],
    'rot'   => ['#fee2e2', '#991b1b', '#ef4444'],
    'gruen' => ['#d1fae5', '#065f46', '#22c55e'],
    'blau'  => ['#dbeafe', '#1e40af', '#3b82f6'],
    'lila'  => ['#e9d5ff', '#6b21a8', '#a855f7'],
];

// ── Page-Title ───────────────────────────────────────────────────────────────
$pageTitle = '📝 Liga-Notizen';
?>

<div class="content-inner" style="padding:24px">

  <!-- ── Liga-Auswahl ─────────────────────────────────────────────────── -->
  <div class="card" style="margin-bottom:20px">
    <h2 style="font-size:.8rem;font-weight:600;margin-bottom:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px">Liga auswählen</h2>
    <form method="get" action="" style="display:flex;gap:10px;align-items:center">
      <input type="hidden" name="action" value="notizen">
      <select name="liga_id" onchange="this.form.submit()"
              style="background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:8px 12px;font-size:.9rem;min-width:200px">
        <option value="0">— Liga wählen —</option>
        <?php foreach ($allLigen as $liga): ?>
          <option value="<?= (int)$liga['id'] ?>" <?= $ligaId === (int)$liga['id'] ? 'selected' : '' ?>>
            <?= h($liga['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <?php if ($ligaId > 0 && $selectedLiga): ?>

    <!-- ── Neue Notiz anlegen ─────────────────────────────────────────── -->
    <div class="card" style="margin-bottom:20px">
      <h2 style="font-size:.8rem;font-weight:600;margin-bottom:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px">
        <?= $editNotiz ? 'Notiz bearbeiten' : 'Neue Notiz für ' . h($selectedLiga['name']) ?>
      </h2>
      <form method="post" action="?action=notiz_save">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= $editNotiz ? (int)$editNotiz['id'] : 0 ?>">
        <input type="hidden" name="liga_id" value="<?= $ligaId ?>">
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px">
          <input type="text" name="titel" placeholder="Titel..."
                 value="<?= $editNotiz ? h($editNotiz['titel']) : '' ?>"
                 style="flex:1;min-width:200px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:8px 12px;font-size:.9rem">
          <select name="farbe" style="background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:8px 12px;font-size:.9rem">
            <?php foreach ($farben as $key => $f): ?>
              <option value="<?= h($key) ?>" <?= ($editNotiz && $editNotiz['farbe'] === $key) ? 'selected' : '' ?>>
                <?= ucfirst($key) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <textarea name="inhalt" placeholder="Notiz-Inhalt..."
                  style="width:100%;min-height:80px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:10px 12px;font-size:.9rem;font-family:inherit;resize:vertical;margin-bottom:12px"><?= $editNotiz ? h($editNotiz['inhalt']) : '' ?></textarea>
        <div style="display:flex;gap:8px">
          <button type="submit" class="btn btn-primary btn-sm" style="text-decoration:none">
            ✓ <?= $editNotiz ? 'Speichern' : 'Anlegen' ?>
          </button>
          <?php if ($editNotiz): ?>
            <a href="?action=notizen&liga_id=<?= $ligaId ?>" class="btn btn-muted btn-sm" style="text-decoration:none">Abbrechen</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- ── Notizen-Liste ─────────────────────────────────────────────── -->
    <?php if (empty($notizen)): ?>
      <div class="card" style="text-align:center;padding:32px;color:var(--muted)">
        <p style="font-size:.95rem">📝 Keine Notizen für diese Liga vorhanden.</p>
        <p style="font-size:.82rem;margin-top:4px">Lege oben die erste Notiz an.</p>
      </div>
    <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        <?php foreach ($notizen as $n): 
          $farbe = $farben[$n['farbe']] ?? $farben['gelb'];
        ?>
          <div style="background:<?= $farbe[0] ?>;border:1px solid <?= $farbe[2] ?>33;border-radius:var(--radius);padding:16px;border-left:4px solid <?= $farbe[2] ?>">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px">
              <h3 style="font-size:.95rem;font-weight:600;color:<?= $farbe[1] ?>;margin:0"><?= h($n['titel']) ?></h3>
              <div style="display:flex;gap:4px;flex-shrink:0">
                <a href="?action=notizen&liga_id=<?= $ligaId ?>&edit=<?= (int)$n['id'] ?>"
                   style="color:var(--muted);text-decoration:none;font-size:.85rem" title="Bearbeiten">✏️</a>
                <form method="post" action="?action=notiz_delete" style="display:inline"
                      onsubmit="return confirm('Notiz wirklich löschen?')">
                  <?= csrfField() ?>
                  <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                  <input type="hidden" name="liga_id" value="<?= $ligaId ?>">
                  <button type="submit" style="background:none;border:none;color:var(--muted);cursor:pointer;font-size:.85rem;padding:0" title="Löschen">🗑️</button>
                </form>
              </div>
            </div>
            <?php if ($n['inhalt'] !== ''): ?>
              <p style="font-size:.85rem;color:<?= $farbe[1] ?>cc;white-space:pre-wrap;margin-bottom:8px"><?= h($n['inhalt']) ?></p>
            <?php endif; ?>
            <div style="font-size:.72rem;color:var(--muted);margin-top:auto">
              <?= h($n['erstellt_von'] ?? '') ?> · <?= date('d.m.Y H:i', strtotime($n['geaendert_am'])) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php elseif ($ligaId === 0): ?>
    <div class="card" style="text-align:center;padding:48px;color:var(--muted)">
      <p style="font-size:1.1rem">📝 Liga-Notizen</p>
      <p style="font-size:.85rem;margin-top:8px">Wähle oben eine Liga aus, um Notizen anzulegen oder zu sehen.</p>
      <p style="font-size:.78rem;margin-top:16px;color:var(--muted)">
        Dieses Addon ist ein Beispiel für das Addon-System von LMOnext.
      </p>
    </div>
  <?php endif; ?>

</div>
