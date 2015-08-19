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
- SQL Database (MySQL & MariaDB zijn getest)
- PHP 5.2
- PHP curl extension (zie beneden)
- PHP pdo_mysql extension (standaard sinds 5.1 op *NIX en sinds 5.3 onder Windows)

Het installatiescript controleert automatisch of aan de voorwaarden is voldaan.

## Nieuwe installatie 
1. Kopieer de bestanden naar de webserver.
2. Roep `install.php` aan en volg de stappen op het scherm.
3. Voeg een uurlijkse cronjob toe die `cronjob.php` aanroept. Bijvoorbeeld:
   `0 * * * * /usr/bin/php /home/htdocs/huis/cronjob.php`  
   Zie beneden voor meer uitleg.  

## Update -Op dit moment mogelijk broken!-
Gebruik deze beschrijving voor een updata vanaf een oudere versie van dit script (van voor dat deze op GitHub stond).

1. Maak een goede back-up!
2. Kopieer en vervang de bestanden in de bestaande installatie met de nieuwere versie.
5. Roep `install.php` aan en volg de stappen op het scherm.
6. Controleer of de cronjob inderdaad het bestand `cronjob.php` aanroept en corrigeer dit eventueel (sommige oude versies gebruikte een andere naam voor dit bestand)

## CURL
Bij veel webhosters is CURL standaard ingeschakeld, maar als je zelf een server beheert, is CURL mogelijk niet standaard is ingeschakeld of je hebt nog geen webserver hebt geïnstalleerd.
### Windows
CURL zit standaard wel in PHP hier, maar is niet direct ingeschakeld. Daarvoor moet je de `php.ini` aanpassen. 
Bij gebruik van XAMPP (ook bij andere installatie) is dit een goede uitleg:
http://stackoverflow.com/questions/3020049/how-to-enable-curl-in-xampp
### Linux
Installeer de `php5-curl` package met het commando `sudo apt-get install php5-curl` en herstart de server. 
Zie hier voor een meer gedetaileerde uitleg: 
http://askubuntu.com/questions/9293/how-do-i-install-curl-in-php5  

## Cronjob
Een Cronjob is een taak die periodiek wordt uitgevoerd op Linux systemen. Dit is nodig om regelmatig de laatste statistieken van de Youless te downloaden.
#### Linux
Gebruik het commando `crontab -e` om de cronjobs aan te kunnen passen. Zie het voorbeeld hierboven (onder `setup`) voor hoe het commando er ongeveer uit dient te zien.

Voor een uitgebreide tutorial in het gebruik van Crontab kun je hier kijken:
http://www.cyberciti.biz/faq/how-do-i-add-jobs-to-cron-under-linux-or-unix-oses/
#### Windows
Onder windows kun je de Task Scheduler gebruiken om regelmatig het script aan te roepen. 
Hier staat een duidelijke tutorial hoe dat gaat in de verschillende versies van windows:
http://www.7tutorials.com/how-create-task-basic-task-wizard  

Voeg daarbij een taak toe, ongeveer als deze (natuurlijk de juiste paden gebruiken):
`C:\Xampp\php\php.exe -f C:\Xampp\htdocs\my_script.php`  
De `-f` parameter is hierbij belangrijk om PHP te laten weten dat je een extern bestand wilt uitvoeren.

## Licentie
Copyright 2015. Deze software is beschikbaar onder de GNU General Public License versie 3. Dit is vrije software.

De bijdragers aan deze software zijn:
- -LA-
- J van der Kroon
- Xander
- Michiel Nijkamp
- Paul Cornelissen