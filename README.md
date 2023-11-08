# IPSymconNetatmoAircare

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-6.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

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

Anschluss der Geräte, die von Netatmo unter dem Beriff _Aircare_ zusammengefasst sind:
- Raumluftsensor

## 2. Voraussetzungen

 - IP-Symcon ab Version 6.0
 - ein Netatmo Aircare-Modul
 - den "normalen" Benutzer-Account, der bei der Anmeldung der Geräte bei Netatmo erzeugt wird (https://my.netatmo.com)
 - IP-Symcon Connect<br>
   **oder**<br>
 - einen Account sowie eine "App" bei Netatmo Connect, um die Werte abrufen zu können (https://dev.netatmo.com)<br>
   Achtung: diese App ist nur für den Zugriff auf Netatmo-Aircare-Produkte gedacht; das Modul benutzt den Scope _read_homecoach_.<br>
   Eine gleichzeitige Benutzung der gleichen Netatmo-App für andere Bereiche (z.B. Weather) stört sich gegenseitig.<br>
   Die Angabe des WebHook in der App-Definition ist nicht erforderlich, das führt das IO-Modul selbst durch.

## 3. Installation

### a. Laden des Moduls

**Installieren über den Module-Store**

Die Webconsole von IP-Symcon mit http://<IP-Symcon IP>:3777/console/ öffnen.

Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.1) klicken

Im Suchfeld nun NetatmoAircare eingeben, das Modul auswählen und auf Installieren drücken.

**Installieren über die Modules-Instanz**

Die Konsole von IP-Symcon öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

In dem sich öffnenden Fenster folgende URL hinzufügen:

`https://github.com/demel42/IPSymconNetatmoAircare.git`

und mit _OK_ bestätigen. Ggfs. auf anderen Branch wechseln (Modul-Eintrag editieren, _Zweig_ auswählen).

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_

### b. Einrichtung in IPS

#### NetatmoAircareIO

In IP-Symcon nun unterhalb von _I/O Instanzen_ die Funktion _Instanz hinzufügen_ (_CTRL+1_) auswählen, als Hersteller _Netatmo_ und als Gerät _NetatmoAircare I/O_ auswählen.

In dem Konfigurationsformular nun den gewünschten Zugang wählen, entweder als Nutzer über IP-Symcon Connect oder als Entwickler mit eigenem Entwicklerschlüssel.

**Zugriff mit Netatmo-Benutzerdaten über IP-Symcon Connect**

Hierzu _bei Netatmo anmelden_ auswählen. Es öffnet sich ein Browserfenster mit der Anmeldeseite von Netatmo; hier bitte anmelden. Dann erscheint ein weiteres Fenster

![OAUTH1](docs/netatmo_login_1.png?raw=true "oauth 1")

Hier bitte den Zugriff des _IP-Symcon Netatmo Connector_ akzeptieren; es erscheint 

![OAUTH1](docs/netatmo_login_2.png?raw=true "oauth 2")

Das Browserfenster schliessen.

Anmerkung: auch wenn hier alle möglichen Netamo-Produkte aufgelistet sind, bezieht sich das Login nur auf die Produkte dieses Moduls.

**Zugriff als Entwickler mit eigenem Entwicklerschlüssel**

In dem Konfigurationsdialog die Netatmo-Zugangsdaten eintragen.

#### NetatmoAircareConfig

Dann unter _Konfigurator Instanzen_ analog den Konfigurator _NetatmoAircare Konfigurator_ hinzufügen.

Hier werden alle Aircare-Produkte, die mit dem, in der I/O-Instanz angegebenen, Netatmo-Konto verknüpft sind, angeboten; aus denen wählt man ein Produkt aus.

Mit den Schaltflächen _Erstellen_ bzw. _Alle erstellen_ werden das/die gewählte Produkt anlegt.

Der Aufruf des Konfigurators kann jederzeit wiederholt werden.

Die Produkte werden aufgrund der _Produkt-ID_ identifiziert.

Zu den Geräte-Instanzen werden im Rahmen der Konfiguration Produkttyp-abhängig Variablen angelegt. Zusätzlich kann man in dem Modultyp-spezifischen Konfigurationsdialog weitere Variablen aktivieren.

Die Instanzen können dann in gewohnter Weise im Objektbaum frei positioniert werden.

## 4. Funktionsreferenz

### NetatmoAircareIO

`NetatmoAircare_UpdateData(int $InstanzID)`
ruft die Daten der Netatmo-Aircare-Produkte ab. Wird automatisch zyklisch durch die Instanz durchgeführt im Abstand wie in der Konfiguration angegeben.

## 5. Konfiguration

### NetatmoAircareIO

#### Properties

| Eigenschaft               | Typ      | Standardwert | Beschreibung |
| :------------------------ | :------  | :----------- | :----------- |
| Verbindungstyp            | integer  | 0            | _Netatmo über IP-Symcon Connect_ oder _Netatmo Entwickler-Schlüssel_ |
|                           |          |              | |
| Netatmo-Zugangsdaten      | string   |              | Benutzername und Passwort von https://my.netatmo.com sowie Client-ID und -Secret von https://dev.netatmo.com |
|                           |          |              | |
| Aktualisiere Daten ...    | integer  | 5            | Aktualisierungsintervall, Angabe in Minuten |

#### Schaltflächen

| Bezeichnung                  | Beschreibung |
| :--------------------------- | :----------- |
| bei Netatmo anmelden         | durch Anmeldung bei Netatmo via IP-Symcon Connect |
| Aktualisiere Daten           | führt eine sofortige Aktualisierung durch |

### NetatmoAircareConfig

#### Properties

| Eigenschaft               | Typ      | Standardwert | Beschreibung |
| :------------------------ | :------  | :----------- | :----------- |
| Kategorie                 | integer  | 0            | Kategorie im Objektbaum, unter dem die Instanzen angelegt werden |
| Produkte                  | list     |              | Liste der verfügbaren Produkte |

### NetatmoAircareSensor

#### Properties

werden vom Konfigurator beim Anlegen der Instanz gesetzt.

| Eigenschaft              | Typ      | Standardwert | Beschreibung |
| :----------------------- | :--------| :----------- | :----------- |
| product_type             | integer  |              | Typ des Produktes |
| product_id               | string   |              | ID des Produktes |
|                          |          |              | |
| with_last_contact        | boolean  | Nein         | letzte Kommunikation mit dem Netatmo-Server |
| with_last_measure        | boolean  | Nein         | letzte Messung des Sensors |
| with_wifi_strength       | boolean  | Nein         | Ausgabe des Signal in den Abstufungen: _schlecht_, _mittel_, _gut_|
|                          |          |              | |
| with_absolute_humidity   | boolean  | false        | absolute Luftfeuchtigkeit |
| with_absolute_pressure   | boolean  | false        | absoluter Luftdruck |
| with_dewpoint            | boolean  | false        | Taupunkt |
| with_heatindex           | boolean  | false        | Hitzeindex |
| with_minmax              | boolean  | false        | Ausgabe von Min/Max-Wert (Temperatur) |
|                          |          |              | |
| minutes2fail             | integer  | 30           | Dauer, bis die Kommunikation als gestört gilt |

### Variablenprofile

Es werden folgende Variablenprofile angelegt:
* Integer<br>
NetatmoAircare.CO2, NetatmoAircare.Index, NetatmoAircare.Noise, NetatmoAircare.WifiStrength

* Float<br>
NetatmoAircare.absHumidity, NetatmoAircare.Dewpoint, NetatmoAircare.Heatindex, NetatmoAircare.Humidity, NetatmoAircare.Pressure, NetatmoAircare.Temperatur

## 6. Anhang

GUIDs
- Modul: `{FEE67CA6-D938-284B-181D-20496B7411C2}`
- Instanzen:
  - NetatmoAircareIO: `{070C93FD-9D19-D670-2C73-20104B87F034}`
  - NetatmoAircareConfig: `{F031A9F9-D196-4852-D287-E46A93256F22}`
  - NetatmoAircareSensor: `{F3940032-CC4B-9E69-383A-6FFAD13C5438}`
- Nachrichten:
  - `{076043C4-997E-6AB3-9978-DA212D50A9F5}`: an NetatmoAircareIO
  - `{53264646-2842-AA77-59F7-3722D44C2100}`: an NetatmoAircareSensor

## 7. Versions-Historie

- 1.9 @ 08.11.2023 08:56
  - Fix: Panel "Experten-Bereich" kann wieder geöffnet werden (Fix zu 1.7)

- 1.8 @ 03.11.2023 11:06
  - Neu: Ermittlung von Speicherbedarf und Laufzeit (aktuell und für 31 Tage) und Anzeige im Panel "Information"
  - update submodule CommonStubs

- 1.7 @ 16.07.2023 11:32
  - Hotfix: Umgehung des Problems, das ein Login mit Entwicklerschlüssel nicht mehr möglich ist

- 1.6 @ 04.07.2023 14:44
  - Fix: Schreibfehler korrigiert
  - Vorbereitung auf IPS 7 / PHP 8.2
  - update submodule CommonStubs
    - Absicherung bei Zugriff auf Objekte und Inhalte

- 1.5.3 @ 12.10.2022 14:44
  - Konfigurator betrachtet nun nur noch Geräte, die entweder noch nicht angelegt wurden oder mit dem gleichen I/O verbunden sind
  - update submodule CommonStubs

- 1.5.2 @ 07.10.2022 13:59
  - update submodule CommonStubs
    Fix: Update-Prüfung wieder funktionsfähig

- 1.5.1 @ 16.08.2022 10:10
  - update submodule CommonStubs
    Fix: in den Konfiguratoren war es nicht möglich, eine Instanz direkt unter dem Wurzelverzeichnis "IP-Symcon" zu erzeugen

- 1.5 @ 08.07.2022 15:32
  - einige Funktionen (GetFormElements, GetFormActions) waren fehlerhafterweise "protected" und nicht "private"
  - interne Funktionen sind nun private und ggfs nur noch via IPS_RequestAction() erreichbar
  - Fix: Angabe der Kompatibilität auf 6.2 korrigiert
  - Verbesserung: IPS-Status wird nur noch gesetzt, wenn er sich ändert
  - update submodule CommonStubs
    Fix: Ausgabe des nächsten Timer-Zeitpunkts
    Fix: keine korrekte Registrierung für OAuth

- 1.4.2 @ 17.05.2022 15:38
  - update submodule CommonStubs
    Fix: Absicherung gegen fehlende Objekte

- 1.4.1 @ 10.05.2022 15:06
  - update submodule CommonStubs
  - SetLocation() -> GetConfiguratorLocation()
  - weitere Absicherung ungültiger ID's

- 1.4 @ 03.05.2022 15:13
  - Anpassungen an IPS 6.2 (Prüfung auf ungültige ID's)
  - IPS-Version ist nun minimal 6.0
  - Anzeige der Referenzen der Instanz incl. Statusvariablen und Instanz-Timer
  - Implememtierung einer Update-Logik
  - diverse interne Änderungen

- 1.3 @ 14.07.2021 18:13
  - PHP_CS_FIXER_IGNORE_ENV=1 in github/workflows/style.yml eingefügt
  - Schalter "Instanz ist deaktiviert" umbenannt in "Instanz deaktivieren"

- 1.2 @ 12.09.2020 12:18
  - LICENSE.md hinzugefügt
  - Nutzung von HasActiveParent(): Anzeige im Konfigurationsformular sowie entsprechende Absicherung von SendDataToParent()
  - lokale Funktionen aus common.php in locale.php verlagert
  - Traits des Moduls haben nun Postfix "Lib"
  - define's durch statische Klassen-Variablen ersetzt

- 1.1 @ 06.07.2020 15:12
  - Einrichtung php-cs-fixer und github-workflows

- 1.0 @ 30.06.2020 18:37
  - Initiale Version
