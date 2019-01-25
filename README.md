# Adatbázisok közötti konzisztencia ellenörző

## Konfiguráció

- /inc/config.php-ba fel kell venni az ellenőrízendő adatbázisokat, tömbösített formában
Megadható:  
-- host  
-- username  
-- password  
-- port  
-- használandó adatbázis  
- php-t be lehet állítani a $phpRoute változóba (pl: php vagy /usr/bin/php). Szükséges a popen használatához

## Futtatás
 - linux alatt van lehetőség ./run.sh parancs futtatására, ami - elméletileg - meghatározza az éppen aktuális php útvonalát, és el is indítja az index.php-t
 - php index.php

## Ellenőrzések

### Egész adatbázis szerkezetre:
- SHOW TABLES | eredmény:(md5(serialize($array)) hash figyelése
- SHOW SLAVE STATUS | Seconds_Behind_Master értéke, ha nem 0, baj van

### Adatbázison belül táblákra egyesével:

Foreach($tables...)
- TABLE LOCK  
-- SELECT count(*) FROM %tablename%  
-- SHOW TABLE STATUS LIKE '%tablename%' | Auto_increment mező értékének figyelése  
-- SHOW CREATE TABLE %tablename% | md5(serialize($array)) hash figyelése  
-- CHECKSUM TABLE %tablename% értékének ellenőrzése  
- TABLE UNLOCK  

EndForeach;


