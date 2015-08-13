# Youless Monitor

## Features 
- Live weergave
- Dag weegave
- Week weergave
- Maand weergave
- Scheiding hoog/laag tarief
- Scheiding hoog/laag verbruik
- Hoog/laag verbruik weekend/feestdagen (zie tabel feestdagen in de database. Er is nog geen onderhouds form)
- Verbruiksdata opslag met 1 record per minuut, met een unique key op het tijdstip zodat een frequentere update mogelijk is waardoor de kans op verlies van data kleiner is.
- (dag/week) Grafiek start op het vroegst gemeten tijdstip, en niet altijd op 00:00 (eigenlijk alleen nodig als je net met de youless begint)

## Eisen
- MySQL DB
- PHP 5.2
- PHP curl extension
- PHP pdo_mysql extension

## Setup 
1. Pas 'settings.inc.php.example' aan en hernoem het naar 'settings.inc.php' (in de map 'inc')
2. Nieuwe setup: roep 'install.php' aan.
3. Bestaande setup/database: roep 'update.php' aan
4. Voeg een uurlijkse cronjob toe die 'cronjob.php' aanroept
   `0 * * * * /usr/bin/php /home/htdocs/huis/cronjob.php`
5. Default username/password is admin/admin

## Licentie
Copyright 2015. Deze software is beschikbaar onder de GNU General Public License versie 3. Dit is vrije software.

De bijdragers aan deze software zijn:
- -LA-
- J van der Kroon
- Xander
- Michiel Nijkamp
- Paul Cornelissen