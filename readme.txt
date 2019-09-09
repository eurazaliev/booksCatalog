1. run git clone https://github.com/eurazaliev/booksCatalog
2. run composer update
3. make shure the "logs" dir is writable to the user that going to start script
4. edit the "src/db.ini" file with your mysql/mariadb credintials. You should use the db.ini.dist as example, just rename to db.ini and change the db access parametres.
5. run php inidex.php
6. logs will be placed to the logs dir.