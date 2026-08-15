<?php
/**
 * Project: LMOnext
 * Filename: addon/liga-notizen/handler_notizen.php
 * Fileversion: 1.0.0
 *
 * PHP version 8.2
 *
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * ── POST-Handler für Liga-Notizen ────────────────────────────────────────────
 *
 * Behandelt alle POST-Actions für das Notizen-Addon:
 *   - notiz_save:    Neue Notiz anlegen oder vorhandene aktualisieren
 *   - notiz_delete:  Notiz löschen
 *
 * Beim ersten Aufruf wird die DB-Tabelle lmo_liga_notizen automatisch angelegt
 * (Lazy Migration).
 */

declare(strict_types=1);

// ── Datenbanktabelle anlegen (Lazy Migration) ───────────────────────────────
function ensureNotizenTable(): void
{
    static $done = false;
    if ($done) return;
    try {
        getDB()->exec("CREATE TABLE IF NOT EXISTS " . tbl('liga_notizen') . " (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            liga_id    INT NOT NULL,
            titel      VARCHAR(200) NOT NULL DEFAULT '',
            inhalt     TEXT NULL,
            farbe      VARCHAR(20) NOT NULL DEFAULT 'gelb',
            erstellt_von VARCHAR(100) NULL,
            erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            geaendert_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_liga (liga_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $done = true;
    } catch (Throwable $e) {
        // Fehler stillschweigend ignorieren — die View zeigt dann "keine Notizen"
    }
}

// ── Hook: Notizen löschen, wenn eine Liga gelöscht wird ──────────────────────
function notizenOnLigaDeleted(array $data): array
{
    $ligaId = (int)($data['liga_id'] ?? 0);
    if ($ligaId <= 0) return $data;
    try {
        $db = getDB();
        $stmt = $db->prepare('DELETE FROM ' . tbl('liga_notizen') . ' WHERE liga_id = ?');
        $stmt->execute([$ligaId]);
    } catch (Throwable) {}
    return $data;
}

// ── POST-Handler ──────────────────────────────────────────────────────────────

// Notiz speichern (neu oder bearbeiten)
if ($action === 'notiz_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = (int)($_POST['id'] ?? 0);
    $ligaId  = (int)($_POST['liga_id'] ?? 0);
    $titel   = trim($_POST['titel'] ?? '');
    $inhalt  = trim($_POST['inhalt'] ?? '');
    $farbe   = $_POST['farbe'] ?? 'gelb';
    $farben  = ['gelb', 'rot', 'gruen', 'blau', 'lila'];
    if (!in_array($farbe, $farben, true)) $farbe = 'gelb';

    if ($ligaId <= 0) {
        flash('Liga-ID fehlt.', 'error');
        redirect('?action=notizen');
    }
    if ($titel === '') {
        flash('Titel darf nicht leer sein.', 'error');
        redirect('?action=notizen&liga_id=' . $ligaId);
    }

    ensureNotizenTable();
    try {
        $db = getDB();
        if ($id > 0) {
            $stmt = $db->prepare(
                'UPDATE ' . tbl('liga_notizen') . '
                    SET liga_id = ?, titel = ?, inhalt = ?, farbe = ?
                  WHERE id = ?'
            );
            $stmt->execute([$ligaId, $titel, $inhalt, $farbe, $id]);
            flash('Notiz aktualisiert.', 'success');
        } else {
            $stmt = $db->prepare(
                'INSERT INTO ' . tbl('liga_notizen') . ' (liga_id, titel, inhalt, farbe, erstellt_von)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$ligaId, $titel, $inhalt, $farbe, $_SESSION['admin_user'] ?? '']);
            flash('Notiz angelegt.', 'success');
        }
    } catch (Throwable $e) {
        flash('Datenbankfehler: ' . $e->getMessage(), 'error');
    }
    redirect('?action=notizen&liga_id=' . $ligaId);
}

// Notiz löschen
if ($action === 'notiz_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $ligaId = (int)($_POST['liga_id'] ?? 0);

    if ($id <= 0) {
        flash('Ungültige Notiz-ID.', 'error');
        redirect('?action=notizen');
    }

    ensureNotizenTable();
    try {
        $db = getDB();
        $stmt = $db->prepare('DELETE FROM ' . tbl('liga_notizen') . ' WHERE id = ?');
        $stmt->execute([$id]);
        flash('Notiz gelöscht.', 'success');
    } catch (Throwable $e) {
        flash('Fehler beim Löschen: ' . $e->getMessage(), 'error');
    }
    redirect('?action=notizen&liga_id=' . $ligaId);
}
