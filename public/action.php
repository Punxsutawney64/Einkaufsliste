<?php
declare(strict_types=1);
require __DIR__ . '/src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
requireCsrf();
$action = (string) ($_POST['action'] ?? '');

try {
    if ($action === 'login') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $stmt = db()->prepare('SELECT ID, NAME, PASSWORD, ADMIN FROM `USER` WHERE `NAME` = ? LIMIT 1');
        $stmt->execute([$name]);
        $record = $stmt->fetch();
        $stored = (string) ($record['PASSWORD'] ?? '');
        $isHash = password_get_info($stored)['algo'] !== null;
        $valid = $record && ($isHash ? password_verify($password, $stored) : hash_equals($stored, $password));
        if (!$valid) {
            flash('Benutzername oder Passwort ist falsch.', 'error');
            redirect('index.php');
        }
        if (!$isHash) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = db()->prepare('UPDATE `USER` SET `PASSWORD` = ? WHERE ID = ?');
            $update->execute([$hash, $record['ID']]);
        }
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $record['ID'], 'name' => $record['NAME'], 'admin' => (bool) $record['ADMIN']];
        flash('Willkommen, ' . $record['NAME'] . '!');
        redirect('index.php?tab=shopping');
    }

    if ($action === 'logout') {
        requireLogin();
        $_SESSION = [];
        session_destroy();
        redirect('index.php');
    }

    requireLogin();
    $tab = (string) ($_POST['tab'] ?? 'shopping');

    if (in_array($action, ['lookup_add', 'lookup_update', 'lookup_delete', 'lookup_move'], true)) {
        requireAdmin();
        $kind = (string) ($_POST['kind'] ?? '');
        $table = ['section' => 'SECTION', 'location' => 'HOME_LOCATION'][$kind] ?? '';
        if (!$table) {
            throw new RuntimeException('Ungültiger Datentyp.');
        }
        $id = postInt('id');
        if ($action === 'lookup_add' || $action === 'lookup_update') {
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name === '') throw new RuntimeException('Bitte eine Bezeichnung eingeben.');
            if (!uniqueName($table, $name, $action === 'lookup_update' ? $id : 0)) {
                throw new RuntimeException('Diese Bezeichnung ist bereits vorhanden.');
            }
            if ($action === 'lookup_add') {
                $stmt = db()->prepare("INSERT INTO `$table` (`NAME`, `ORDER_NR`) SELECT ?, COALESCE(MAX(`ORDER_NR`), 0) + 1 FROM `$table`");
                $stmt->execute([$name]);
            } else {
                if (!$id) throw new RuntimeException('Bitte einen Eintrag auswählen.');
                $stmt = db()->prepare("UPDATE `$table` SET `NAME` = ? WHERE ID = ?");
                $stmt->execute([$name, $id]);
            }
        } elseif ($action === 'lookup_delete') {
            if (!$id) throw new RuntimeException('Bitte einen Eintrag auswählen.');
            $stmt = db()->prepare("DELETE FROM `$table` WHERE ID = ?");
            $stmt->execute([$id]);
        } else {
            if (!$id) throw new RuntimeException('Bitte einen Eintrag auswählen.');
            $direction = ($_POST['direction'] ?? '') === 'up' ? 'up' : 'down';
            db()->beginTransaction();
            $current = db()->prepare("SELECT ID, ORDER_NR FROM `$table` WHERE ID = ? FOR UPDATE");
            $current->execute([$id]);
            $row = $current->fetch();
            if ($row) {
                $op = $direction === 'up' ? '<' : '>';
                $sort = $direction === 'up' ? 'DESC' : 'ASC';
                $other = db()->prepare("SELECT ID, ORDER_NR FROM `$table` WHERE ORDER_NR $op ? ORDER BY ORDER_NR $sort, ID $sort LIMIT 1 FOR UPDATE");
                $other->execute([$row['ORDER_NR']]);
                if ($swap = $other->fetch()) {
                    $update = db()->prepare("UPDATE `$table` SET ORDER_NR = ? WHERE ID = ?");
                    $update->execute([$swap['ORDER_NR'], $row['ID']]);
                    $update->execute([$row['ORDER_NR'], $swap['ID']]);
                }
            }
            db()->commit();
        }
        flash('Änderung gespeichert.');
        $selected = $action === 'lookup_move' && $id > 0 ? '&selected=' . $id : '';
        redirect('index.php?tab=' . urlencode($tab) . $selected);
    }

    if (in_array($action, ['article_add', 'article_update', 'article_delete'], true)) {
        $id = postInt('id');
        if ($action === 'article_delete') {
            requireAdmin();
            if (!$id) throw new RuntimeException('Bitte einen Artikel auswählen.');
            $stmt = db()->prepare('DELETE FROM ARTICLE WHERE ID = ?');
            $stmt->execute([$id]);
        } else {
            $name = trim((string) ($_POST['name'] ?? ''));
            $section = postInt('section_id');
            $location = postInt('location_id');
            $reserve = isset($_POST['reserve']) ? 1 : 0;
            if ($name === '' || !$section || !$location) throw new RuntimeException('Bitte alle Pflichtfelder ausfüllen.');
            if (!uniqueName('ARTICLE', $name, $action === 'article_update' ? $id : 0)) throw new RuntimeException('Dieser Artikelname ist bereits vorhanden.');
            if ($action === 'article_add') {
                $stmt = db()->prepare('INSERT INTO ARTICLE (NAME, RESERVE, ORDER_NR, SECTION_ID, HOME_LOCATION_ID) SELECT ?, ?, COALESCE(MAX(ORDER_NR), 0) + 1, ?, ? FROM ARTICLE');
                $stmt->execute([$name, $reserve, $section, $location]);
            } else {
                if (!$id) throw new RuntimeException('Bitte einen Artikel auswählen.');
                $stmt = db()->prepare('UPDATE ARTICLE SET NAME=?, RESERVE=?, SECTION_ID=?, HOME_LOCATION_ID=? WHERE ID=?');
                $stmt->execute([$name, $reserve, $section, $location, $id]);
            }
        }
        flash('Artikel gespeichert.');
        redirect('index.php?tab=articles');
    }

    if ($action === 'shopping_save') {
        $counts = (array) ($_POST['count'] ?? []);
        $adhoc = str_replace(["\r\n", "\r"], "\n", (string) ($_POST['adhoc'] ?? ''));
        $adhocLength = function_exists('mb_strlen') ? mb_strlen($adhoc) : strlen($adhoc);
        if ($adhocLength > 255) throw new RuntimeException('Der Ad-hoc-Text darf höchstens 255 Zeichen lang sein.');
        $userId = (int) user()['id'];
        db()->beginTransaction();
        $updateAdhoc = db()->prepare('UPDATE `USER` SET ADHOC = ? WHERE ID = ?');
        $updateAdhoc->execute([$adhoc === '' ? null : $adhoc, $userId]);
        $delete = db()->prepare('DELETE FROM ARTICLE_TO_SHOP WHERE USER_ID = ?');
        $delete->execute([$userId]);
        $insert = db()->prepare('INSERT INTO ARTICLE_TO_SHOP (USER_ID, ARTICLE_ID, `COUNT`) SELECT ?, ID, ? FROM ARTICLE WHERE ID = ?');
        foreach ($counts as $articleId => $count) {
            $articleId = (int) $articleId;
            $count = max(0, min(10, (int) $count));
            if ($articleId && $count > 0) $insert->execute([$userId, $count, $articleId]);
        }
        db()->commit();
        flash('Einkaufsliste gesichert.');
        redirect('index.php?tab=shopping');
    }

    if ($action === 'shopping_reset') {
        $stmt = db()->prepare('DELETE FROM ARTICLE_TO_SHOP WHERE USER_ID = ?');
        $stmt->execute([user()['id']]);
        flash('Einkaufsliste zurückgesetzt.');
        redirect('index.php?tab=shopping');
    }

    if ($action === 'buy_update') {
        $completed = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['completed'] ?? [])))));
        $completedAdhoc = array_fill_keys(array_map('intval', (array) ($_POST['completed_adhoc'] ?? [])), true);
        $counts = (array) ($_POST['count'] ?? []);
        $userId = (int) user()['id'];
        db()->beginTransaction();
        if ($completed) {
            $placeholders = implode(',', array_fill(0, count($completed), '?'));
            $stmt = db()->prepare("DELETE FROM ARTICLE_TO_SHOP WHERE USER_ID = ? AND ARTICLE_ID IN ($placeholders)");
            $stmt->execute([$userId, ...$completed]);
        }
        $completedSet = array_fill_keys($completed, true);
        $updateCount = db()->prepare('UPDATE ARTICLE_TO_SHOP SET `COUNT` = ? WHERE USER_ID = ? AND ARTICLE_ID = ?');
        foreach ($counts as $articleId => $count) {
            $articleId = (int) $articleId;
            if (!$articleId || isset($completedSet[$articleId])) continue;
            $count = max(1, min(10, (int) $count));
            $updateCount->execute([$count, $userId, $articleId]);
        }
        if ($completedAdhoc) {
            $select = db()->prepare('SELECT ADHOC FROM `USER` WHERE ID = ? FOR UPDATE');
            $select->execute([$userId]);
            $adhocLines = preg_split('/\R/u', (string) $select->fetchColumn());
            $remaining = array_values(array_filter($adhocLines, static fn($line, $index) => !isset($completedAdhoc[$index]), ARRAY_FILTER_USE_BOTH));
            $update = db()->prepare('UPDATE `USER` SET ADHOC = ? WHERE ID = ?');
            $newAdhoc = implode("\n", $remaining);
            $update->execute([$newAdhoc === '' ? null : $newAdhoc, $userId]);
        }
        db()->commit();
        flash('Erledigte Artikel wurden von der Einkaufsliste entfernt.');
        redirect('index.php?tab=buy');
    }

    throw new RuntimeException('Unbekannte Aktion.');
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    flash($e instanceof PDOException ? 'Die Datenbank konnte die Änderung nicht ausführen.' : $e->getMessage(), 'error');
    redirect('index.php?tab=' . urlencode((string) ($_POST['tab'] ?? 'shopping')));
}
