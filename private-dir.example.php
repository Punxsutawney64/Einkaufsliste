<?php
declare(strict_types=1);

/**
 * Vorlage für die serverseitige Datei `private-dir.php`.
 *
 * Diese Datei gehört NICHT ins Git-Repository und NICHT nach public/.
 * Sie wird einmalig manuell direkt im Document Root des Webservers abgelegt
 * (neben index.php, also im deployten Inhalt von public/) und liefert den
 * absoluten Pfad zu einem Ordner ausserhalb des Document Root, in dem die
 * kritischen Dateien (siehe private/ in diesem Repo, z.B. config.php und
 * die SQL-Dateien) auf dem Server liegen.
 *
 * Da diese Datei von PHP ausgeführt wird, liefert ein direkter HTTP-Aufruf
 * (GET /private-dir.php) keine Ausgabe und verrät den Pfad nicht.
 *
 * Vorgehen auf dem Server:
 * 1. Kopie dieser Datei nach `private-dir.php` neben `index.php` im
 *    Document Root.
 * 2. Pfad unten anpassen.
 * 3. `config.php` (mit den echten Produktions-Zugangsdaten) sowie die
 *    SQL-Dateien in diesen Ordner legen (ausserhalb des Document Root!).
 */
return '/pfad/ausserhalb/des/document-root/private';
