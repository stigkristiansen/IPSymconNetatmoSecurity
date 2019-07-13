# IPSymconNetatmoSecurity

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Module-Version](https://img.shields.io/badge/Modul_Version-1.0-blue.svg)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![StyleCI](https://github.styleci.io/repos/192195342/shield?branch=master)](https://github.styleci.io/repos/192195342)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

Anschluss der Geräte, die von Netatmo unter dem Beriff _Security_ zusammengefasst sind:
- Aussenkamera (_Outdoor_ bzw. _Presence_)
- Innenkamera (_Indoor_ bzw. _Welcome_)
- Rauchmelder
Hinweis: für den Rauchmelder gibt es mangels Testmöglichkeit noch keine Implementierung.

Je nach Produktyp umfasst das Modul folgende Funktionen:
- Abruf des Status
- Speicherung der Ereignisse für eine definierbaren Zeitraum
- Empfang von Mitteilungen vua WebHook
- Ermittlung der URL's zu Abruf von Videos und Snapshots (Life und historisch)
- Einbindung der optional von Netatmo per _ftp_ übertragenen Videos
- Steuerung (Kamera aus/ein, Licht)

## 2. Voraussetzungen

 - IP-Symcon ab Version 5<br>
 - ein Netatmo Security-Modul (also Kamera oder Rauchmelder)
 - den "normalen" Benutzer-Account, der bei der Anmeldung der Geräte bei Netatmo erzeugt wird (https://my.netatmo.com)
 - einen Account sowie eine "App" bei Netatmo Connect, um die Werte abrufen zu können (https://dev.netatmo.com)<br>
   Achtung: diese App ist nur für den Zugriff auf Netatmo-Security-Produkte gedacht; das Modul benutzt die Scopes _read_presence access_presence read_camera write_camera access_camera read_smokedetector_.<br>
   Eine gleichzeitige Benutzung der gleichen Netatmo-App für andere Bereiche (z.B. Weather) stört sich gegenseitig.<br>
   Die Angabe des WebHook in der App-Definition ist nicht erforderlich, das führt das IO-Modul selbst durch.

## 3. Installation

### a. Laden des Moduls

Die Konsole von IP-Symcon öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

In dem sich öffnenden Fenster folgende URL hinzufügen:

`https://github.com/demel42/IPSymconNetatmoSecurity.git`

und mit _OK_ bestätigen. Ggfs. auf anderen Branch wechseln (Modul-Eintrag editieren, _Zweig_ auswählen).

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_

### b. Einrichtung in IPS

In IP-Symcon nun unterhalb von _I/O Instanzen_ die Funktion _Instanz hinzufügen_ (_CTRL+1_) auswählen, als Hersteller _Netatmo_ und als Gerät _NetatmoSecurity I/O_ auswählen.

In dem Konfigurationsdialog die Netatmo-Zugangsdaten eintragen.

Dann unter _Konfigurator Instanzen_ analog den Konfigurator _NetatmoSecurity Konfigurator_ hinzufügen.

Hier werden alle Security-Produkte, die mit dem, in der I/O-Instanz angegebenen, Netatmo-Konto verknüpft sind, angeboten; aus denen wählt man ein Produkt aus.

Mit den Schaltflächen _Erstellen_ bzw. _Alle erstellen_ werden das/die gewählne Produkt anlegt.

Zur Zeit werden die folgende Produkttypen unterstützt:

| Produkt-Typ | Bezeichnung               | Modul |
| :---------- | :------------------------ | :---- | 
| NOC         | Outdoor Camera (Presence) | NetatmoSecurityCamera |
| NACamera    | Indoor Camera (Welcome)   | NetatmoSecurityCamera |

Hinweis:
- _Presence_ konnte ich selbst testen
- _Welcome_ ist, soweit es die Kamera betrifft, implementiert, da fehlt aber sicherlich noch das ein oder andere. Wenn jemand testet, würde ich das gerne ergänzen
- _Rauchmelder_ würde ich ergänzen, wenn jemand teste.

Der Aufruf des Konfigurators kann jederzeit wiederholt werden.

Die Produkte werden aufgrund der _Produkt-ID_ sowie der _Haus-ID_ identifiziert.

Zu den Geräte-Instanzen werden im Rahmen der Konfiguration Produkttyp-abhängig Variablen angelegt. Zusätzlich kann man in dem Modultyp-spezifischen Konfigurationsdialog weitere Variablen aktivieren.

Die Instanzen können dann in gewohnter Weise im Objektbaum frei positioniert werden.

## 4. Funktionsreferenz

### NetatmoSecurityIO

`NetatmoSecurityIO_UpdateData(int $InstanzID)`
ruft die Daten der Netatmo-Security-Produkte ab. Wird automatisch zyklisch durch die Instanz durchgeführt im Abstand wie in der Konfiguration angegeben.

### NetatmoSecurityCamera

`NetatmoSecurityCamera_GetVpnUrl(int $InstanzID)`<br>
liefert die externe URL der Kamera zurück

`NetatmoSecurityCamera_GetLocalUrl(int $InstanzID)`<br>
liefert die interne URL der Kamera zurück oder _false_, wenn nicht vorhanden

`NetatmoSecurityCamera_GetLiveVideoUrl(int $InstanzID, string $resolution)`
liefert die (interne oder externe) URL des Live-Videos der Kamera zurück oder _false_, wenn nicht vorhanden.
_resolution_ ist _poor_, _low_, _medium_, _high_.

`NetatmoSecurityCamera_GetLiveSnapshotUrl(int $InstanzID)`<br>
liefert die (interne oder externe) URL des Live-Snapshots der Kamera zurück oder _false_, wenn nicht vorhanden

`NetatmoSecurityCamera_GetVideoUrl(int $InstanzID, string $video_id, string $resolution)`<br>
liefert die (interne oder externe) URL eines gespeicherten Videos zurück oder _false_, wenn nicht vorhanden.
_resolution_ ist _poor_, _low_, _medium_, _high_.

`NetatmoSecurityCamera_GetPictureUrl(int $InstanzID, string $id, string $key)`<br>
liefert die URL eines gespeicherten Bildes (_Snapshot_ oder _Vignette_) zurück oder _false_, wenn nicht vorhanden

`NetatmoSecurityCamera_GetVideoFilename(int $InstanzID, string $video_id, int $tstamp)`<br>
liefert den Dateiname eines gespeicherten Videos zurück oder _false_, wenn nicht vorhanden (setzt die Übertragung der Videos per FTP voraus)

`NetatmoSecurityCamera_GetEvents(int $InstanzID)`<br>
liefert alle gespeicherten Ereignisse der Kamera; Datentyp siehe _Events_.
Die Liste ist zeitlich aufsteigend sortiert.

`NetatmoSecurityCamera_SearchEvent(int $InstanzID, string $event_id)`<br>
Sucht einen Event in den gespeicherten Events

`NetatmoSecurityCamera_SearchSubEvent(int $InstanzID, string $subevent_id)`<br>
Sucht einen Sub-Event in den gespeicherten Events

`NetatmoSecurityCamera_GetNotifications(int $InstanzID)`<br>
liefert alle gespeicherten Benachrichtigungen der Kamera; Datentyp siehe _Notifications_.
Die Liste ist zeitlich aufsteigend sortiert.

`NetatmoSecurityCamera_CleanupVideoPath(int $InstanzID, bool $verboѕe = false)`<br>
bereinigt das Verzeichnis der (per FTP übertragenen) Videos

`NetatmoSecurityCamera_SwitchLight(int $InstanzID, int $mode)`<br>
schaltet das Licht (0=aus, 1=ein, 2=auto)

`NetatmoSecurityCamera_DimLight(int $InstanzID, int $intensity)`<br>
dimmt das Licht (0..100%). <br>
Hinweis: es gibt keine Rückmeldung über die aktuelle Licht-Intensität

`NetatmoSecurityCamera_SwitchCamera(int $InstanzID, int $mode)`<br>
schaltet die Kamera (0=aus, 1=ein)

`NetatmoSecurityCamera_GetVideoUrl4Event(int $InstanzID, string $event_id, string $resolution)`<br>
Liefert die URL des Videos zu einem bestimmten Event

`NetatmoSecurityCamera_GetSnapshotUrl4Subevent(int $InstanzID, string $subevent_id)`<br>
Liefert die URL des Snapshot zu einem bestimmten Sub-Event.
Anmerkung: als Snapshot bezeichnet Netatmo in diesem Zusammenhang das Bild, das zum Erzeugen eines Ereingnisses geführt hat

`NetatmoSecurityCamera_GetVignetteUrl4Subevent(int $InstanzID, string $subevent_id)`<br>
Liefert die URL der Vignette zu einem bestimmten Sub-Event.
Anmerkung: als Vignette bezeichnet Netatmo in diesem Zusammenhang den Bildausschnitt, das zum Erzeugen eines Ereingnisses geführt hat

#### WebHook

Das Modul stellt ein WebHook zur Verfügung, mit dem auf die Videos und Bilder zurückgegriffen werden kann (siehe Konfigurationsdialog).

| Kommando                           | Bedeutung |
| :--------------------------------- | :-------- |
| video?life                         | liefert die (interne oder externe) URL zu dem Life-Video |
| video?event_id=\<event-id\>          | liefert die URL der lokal gespeicherten MP4-Videodatei oder die (interne oder externe) URL zu dem Video |
|                                    | |
| snapshot?life                      | liefert die (interne oder externe) URL zu dem Life-Snapshot |
| snapshot?subevent_id=\<subevent-id\> | liefert die (interne oder externe) URL zu dem Snapshot |
|                                    | |
| vignette?subevent_id=\<subevent-id\> | liefert die (interne oder externe) URL zu der Vignette |

Das _Kommando_ wird an den angegenegen WebHook angehängt.

Bei allen Aufrufen zu Videos kann die Option _resolution=\<resolution\>_ hinzugefügt werden; mögliche Werte sind  _poor_, _low_, _medium_, _high_, Standardwert ist _high_.

Bei allen Aufrufen kann Option _result_ angfügt werden

| Option | Beschreibung |
| :----- | :------------| 
| html   | Standardwert, liefert einen kleine HMTL-Code, der per _iframe_ eigebunden werden kann |
| url    | es wird die reine URL, ansonsten ein einbettbarer HTML-Code geliefert |
| custon | es wird das in der Konfiguration angegebene Script aufgerufen.
Dem Script wird die ermittelte _url_ übergeben sowie *_SERVER* und *_GET*. Das Ergebnis wird als Ergebnis des Webhook ausgegeben |

Hinweis zu dem Video: die lokalen Kopien der Videos werden als MP4 von Netatmo geliefert. Das Abspielen von MP4-Dateien funktioniert nur bei IPS >= 5.2 oder mit dem Firefox-Browser und daher wird unter diesen Umständen die lokale Datei ignoriert.

## 5. Konfiguration

### NetatmoSecurityIO

#### Properties

| Eigenschaft               | Typ      | Standardwert | Beschreibung |
| :------------------------ | :------  | :----------- | :----------- |
| Netatmo-Zugangsdaten      | string   |              | Benutzername und Passwort von https://my.netatmo.com sowie Client-ID und -Secret von https://dev.netatmo.com |
|                           |          |              | |
| Ignoriere HTTP-Fehler     | integer  | 0            | Da Netatmo häufiger HTTP-Fehler meldet, wird erst ab dem X. Fehler in Folge reagiert |
|                           |          |              | |
| Aktualisiere Daten ...    | integer  | 5            | Aktualisierungsintervall, Angabe in Minuten |
|                           |          |              | |
| Webbook registrieren      | boolean  | Nein         | Webhook zur Übernahme der Benachrichtigungen von Netatmo |
| Basis-URL                 | string   |              | URL, unter der IPS erreichbar ist; wird nichts angegeben, wird die IPS-Connect-URL verwendet|
|                           |          |              | |
| Aktualisiere Daten ...    | integer  | 5            | Aktualisierungsintervall, Angabe in Minuten |

#### Schaltflächen

| Bezeichnung                  | Beschreibung |
| :--------------------------- | :----------- |
| Aktualisiere Daten           | führt eine sofortige Aktualisierung durch |

### NetatmoSecurityCamera

#### Properties

werden vom Konfigurator beim Anlegen der Instanz gesetzt.

| Eigenschaft              | Typ            | Standardwert | Beschreibung |
| :----------------------- | :------------- | :----------- | :----------- |
| Produkt-Typ              | string         |              | Identifikation, z.Zt _NACamera_, _NOC_ |
| Produkt-ID               | string         |              | ID des Produktes |
| Haus-ID                  | string         |              | ID des "Hauses" |
|                          |                |              | |
| letzte Kommunikation     | UNIX-Timestamp | Nein         | letzte Kommunikation mit dem Netatmo-Server |
| letztes Ereignis         | UNIX-Timestamp | Nein         | Zeitpunkt der letzten Änderung an Ereignissen durch Ereignis-Abruf |
| letzte Benachrichtigung  | UNIX-Timestamp | Nein         | Zeitpunkt der letzten Benachrichtigung von Netatmo |
|                          |                |              | |
| Webhook                  | string         |              | Webhook, um Daten dieser Kamera abzufragen |
|  ... Script              | integer        |              | Script, das dem Aufruf des WebHook aufgerufen werden kann (siehe Aufbau des WebHook) |
|                          |                |              | |
| Ereignisse               |                |              | |
|  ... max. Alter          | integer        | 14           | automatisches Löschen nach Überschreitung des Alters |
|  ... Medienobjekt cachen | boolean        | Nein         | Medien-Objekt cachen, spart Resource, gehlt aber bei einem Absturz verloren |
| Benachrichtigung         |                |              | |
|  ... max. Alter          | integer        | 2            | automatisches Löschen nach Überschreitung des Alters |
|  ... Medienobjekt cachen | boolean        | Nein         | Medien-Objekt cachen, spart Resource, gehlt aber bei einem Absturz verloren |
|                          |                |              | |
| FTP-Verzeichnis          |                |              | |
|  ... Verzeichnis         | path           |              | bei relativem Pfad wird IPS-Basisverzeichnis vorangestellt |
|  ... max. Alter          | integer        | 14           | automatisches Löschen nach Überschreitung des Alters |
|                          |                |              | |
| Benachrichtigungen       |                |              | |
|  ... Script              | integer        |              | Script, das beim Emfang einer Benachrichtigung aufgerufen wird |
|                          |                |              | |

Hinweis: damit die Videos abgerufen werden können, müssen diesen unterhalb von _webfront/user_ liegen (zumindestens ist mir keine andere Möglichkeit bekannt). Wenn die Daten auf einem anderen Server (z.B. einem NAS) gespeichert werden, so kann das Verzeichnis ja passend gemountet werden.<br>
Das ist an sich unproblatisch, aber die Standard-Sicherung von IPS sichert das Webhook-Verzeichnis natprlich mit und damit wird die Sicherung deutlich größer.

Warum gibt es die Möglichkeit die per FTP übertragenen Videos einzubinden? Der Zugriff ist schneller und die Darstellung besser, da die Daten nicht von der SD-Karte der Kamera geholt werden müssen.

##### Script
Das Script wird bei Empfang der Nachrіchten ganz am Schluss aufgerufen; ihm wird die _InstanceID_ übergeben.

Ein passendes Code-Fragment für ein Script zur Erstellung einer HTML-Box mit den Benachrichtigungen siehe _docs/process_notification.php_

### Variablenprofile

Es werden folgende Variablenprofile angelegt:
* Integer<br>
NetatmoSecurity.CameraStatus, NetatmoSecurity.CameraAction, <br>
NetatmoSecurity.LightModeStatus, NetatmoSecurity.LightAction, NetatmoSecurity.LightIntensity, <br>
NetatmoSecurity.SDCardStatus, <br>
NetatmoSecurity.PowerStatus

### Datenstrukturen

#### Ereignisse (Events)

| Variable          | Datenty        | optional | Bedeutung |
| :-----------      | :------------- | :------- | :-------- |
| id                | string         | nein     | ID de Ereignisses |
| tstamp            | UNIX-Timestamp | nein     | Zeitpunkt des Ereignisses |
| message           | string         | nein     | Nachrichtentext |
| video_id          | string         | ja       | |
| video_status      | string         | ja       | Ausprägungen: recording, available, deleted |
| person_id         | string         | ja       | |
| is_arrival        | boolen         | ja       | |
| subevents         | Objekt-Liste   | ja       | Liste der Einzel-Ereignisse |

#### Einzel-Ereignisse (Sub-Events)

| Variable          | Datenty        | optional | Bedeutung |
| :---------------- | :------------- | :------- | :-------- |
| id                | string         | ja       | ID des Einzel-Erignisses (siehe Events) |
| tstamp            | UNIX-Timestamp | nein     | Zeitpunkt des Ereignisses |
| event_type        | string         | nein     | Type des Ereignisses |
| message           | string         | nein     | Nachrichtentext |
|                   |                |          | |
| snapshot.id       | string         | ja       | es gibt entweder _id_ + _key_ oder _filename_ |
| snapshot.key      | string         | ja       | |
| snapshot.filename | string         | ja       | |
| vignette.id       | string         | ja       | es gibt entweder _id_ + _key_ oder _filename_ |
| vignette.key      | string         | ja       | |
| vignette.filename | string         | ja       | |

- _event_types_: _human_, _animal_, _vehicle_

#### Benachrichtigungen (Notifications)

| Variable     | Datenty        | optional | Bedeutung |
| :----------- | :------------- | :------- | :-------- |
| id           | string         | nein     | ID der Benachrichtigung |
| tstamp       | UNIX-Timestamp | nein     | Zeitpunkt der Benachrichtigung |
| push_type    | string         | nein     | Art der Benachrichtigung |
| event_type   | string         | nein     | Art des Ereignisses |
| message      | string         | nein     | Nachrichtentext |
| subevent_id  | string         | ja       | ID des Einzel-Erignisses (siehe _Sub-Events_) |
| snapshot_id  | string         | ja       | siehe _Sub-Events_ |
| snapshot_key | string         | ja       | siehe _Sub-Events_ |
| vignette_id  | string         | ja       | siehe _Sub-Events_ |
| vignette_key | string         | ja       | siehe _Sub-Events_ |

- _push_type_:
  - Benachrichtigung mit _Sub-Event_<br>
    _NOC-human_, _NOC-animal_, _NOC-vehicle_
  - sonstige Benachrichtigung:<br>
    _NOC-connection_, _NOC-disconnection_, _NOC-light_mode_, _NOC-movement_, _NOC-off_, _NOC-on_

## 6. Anhang

GUIDs
- Modul: `{99D79F62-B7C8-4A59-9A67-65456C6EE9BB}`
- Instanzen:
  - NetatmoSecurityIO: `{DB1D3629-EF42-4E5E-92E3-696F3AAB0740}`
  - NetatmoSecurityConfig: `{C4834515-843B-4B91-A998-6EA29FD9E7A8}`
  - NetatmoSecurityCamera: `{06D589CF-7789-44B1-A0EC-6F51428352E6}`
- Nachrichten:
  - `{2EEA0F59-D05C-4C50-B228-4B9AE8FC23D5}`: an NetatmoSecurityIO
  - `{5F947426-53FB-4DD9-A725-F95590CBD97C}`: an NetatmoSecurityConfig, NetatmoSecurityCamera

## 7. Versions-Historie

- 1.0 @ 09.07.2019 15:40<br>
  Initiale Version
