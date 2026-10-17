# SHOPPINGLIST

Webapplikation für PHP 8.2 und MariaDB 10.

## Ordnerstruktur

- `public/` – alles, was der Webserver ausliefern darf (Document Root). Wird
  beim Deploy 1:1 in den Root-Ordner der Applikation auf dem Server kopiert.
- `private/` – kritische Dateien (u.a. `config.php` mit den DB-Zugangsdaten
  sowie die SQL-Dateien). Wird **nicht** deployt und darf auf dem Server nicht
  im Document Root liegen.
- `private-dir.example.php` – Vorlage für `private-dir.php`, siehe unten.

Die Anwendung ermittelt den Pfad zum privaten Ordner über `privateDir()`
([public/src/bootstrap.php](public/src/bootstrap.php)):

- Existiert im Document Root eine Datei `private-dir.php`, wird deren
  Rückgabewert (ein absoluter Pfad) verwendet. Diese Datei liegt nur auf dem
  Server, ist nicht Teil der Versionsverwaltung und wird nicht per Deploy
  überschrieben. Da sie von PHP ausgeführt wird, verrät ein direkter
  HTTP-Aufruf ihren Inhalt nicht.
- Existiert diese Datei nicht (lokale Entwicklung), wird automatisch der
  Ordner `private/` neben `public/` im Projektverzeichnis verwendet.

## Lokale Entwicklung

1. `private/SHOPPINGLIST.sql` in MariaDB importieren.
2. `php -S localhost:8000 -t public` im Projektverzeichnis ausführen.
3. `private/config.php` enthält die lokalen Zugangsdaten (siehe unten für
   Umgebungsvariablen).

## Deploy auf den Server

1. Inhalt von `public/` in den Document Root der Applikation kopieren.
2. Einen nicht öffentlich erreichbaren Ordner ausserhalb des Document Root
   anlegen und dorthin `config.php` (mit den produktiven Zugangsdaten,
   z.B. ausgehend von `private/config.andromeda.php`) sowie bei Bedarf die
   SQL-Dateien kopieren.
3. `private-dir.example.php` als `private-dir.php` in den Document Root
   kopieren (neben `index.php`) und den Pfad aus Schritt 2 eintragen.
4. Sicherstellen, dass die PHP-Erweiterung `pdo_mysql` aktiviert ist.
5. Mindestens einen Benutzer in `USER` anlegen. Empfohlen ist ein mit
   `password_hash()` erzeugter Wert in `PASSWORD`.

In Produktion können die DB-Werte in `config.php` über die Umgebungsvariablen
`SHOPPINGLIST_DB_HOST`, `SHOPPINGLIST_DB_PORT`, `SHOPPINGLIST_DB_NAME`,
`SHOPPINGLIST_DB_USER` und `SHOPPINGLIST_DB_PASSWORD` überschrieben werden.

Beispiel für einen einmalig in PHP erzeugten Passwort-Hash:

```php
echo password_hash('MeinSicheresPasswort', PASSWORD_DEFAULT);
```

Ein vorhandenes Klartextpasswort wird beim ersten erfolgreichen Login automatisch in einen sicheren Hash umgewandelt.
