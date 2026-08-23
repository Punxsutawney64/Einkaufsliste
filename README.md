# SHOPPINGLIST

Webapplikation für PHP 8.2 und MariaDB 10.

## Installation

1. `SHOPPINGLIST.sql` in MariaDB importieren.
2. Den Projektordner als Document Root eines PHP-8.2-Webservers konfigurieren.
3. Sicherstellen, dass die PHP-Erweiterung `pdo_mysql` aktiviert ist.
4. Mindestens einen Benutzer in `USER` anlegen. Empfohlen ist ein mit `password_hash()` erzeugter Wert in `PASSWORD`.
5. `config.php` ausserhalb öffentlicher Versionsverwaltung halten. In Produktion können die Werte über `SHOPPINGLIST_DB_HOST`, `SHOPPINGLIST_DB_PORT`, `SHOPPINGLIST_DB_NAME`, `SHOPPINGLIST_DB_USER` und `SHOPPINGLIST_DB_PASSWORD` überschrieben werden.

Beispiel für einen einmalig in PHP erzeugten Passwort-Hash:

```php
echo password_hash('MeinSicheresPasswort', PASSWORD_DEFAULT);
```

Ein vorhandenes Klartextpasswort wird beim ersten erfolgreichen Login automatisch in einen sicheren Hash umgewandelt.

