# www.koegler-reisedienst.de
Webseite

# pswd #####################################################
M...
# docker & cowmail #########################################
https://www.bennetrichter.de/anleitungen/mailcow-dockerized/
apt install mc git tmux curlftpfs
...

hubert@koegler-reisedienst.de,edith@koegler-reisedienst.de,michael.krocka@koegler-reisedienst.de
info@koegler-reisedienst.de

# hostname #################################################
mail.koegler-reisedienst.de
sudo hostnamectl set-hostname mail.koegler-reisedienst.de
MX record    @           mail.koegler-reisedienst.de

timedatectl status
sudo timedatectl set-timezone Europe/Berlin

crontab:
@reboot curlftpfs storage_u2024:d93c870761a17c58@io.servercow.de/home/ /home/storage/
0 2 1 * * export NODE_PATH="$(npm config get prefix)/var/www/meister.goip.de/node_modules";cd /var/www/meister.goip.de/rechnung;node /var/www/meister.goip.de/rechnung/html2p
df.js

1 2 1 * * export NODE_PATH="$(npm config get prefix)/var/www/meister.goip.de/node_modules";cd /var/www/meister.goip.de/rechnung;node /var/www/meister.goip.de/rechnung/bermud
a.js

0 23 * * * /var/www/data/bermuda/backup_day.sh
0 23 1 * * /var/www/data/bermuda/backup_mon.sh

0 * * * * /var/www/usr/mail_notify.php S
1 0 * * * /var/www/usr/mail_notify.php T
0 1 * * 1 /var/www/usr/mail_notify.php W

# imap-backup ##############################################
gem install 'imap-backup'
 
.imap_backup/config.json:

{
  "accounts": [
    {
      "username": "info@koegler-reisedienst.de",
      "password": ".tritscher.",
      "local_path": "/home/krocka/.imap-backup/info_koegler-reisedienst.de",
      "folders": [

      ],
      "connection_options": {
        "ssl": {
          "verify_mode": 0
        }
      },
      "connection_options": {
        "ssl": {"verify_mode": 0}
      },
      "server": "www.koegler-reisedienst.de"
    }
  ],
  "debug": false
}

imap-backup backup
imap_backup restore

# mariadb ##################################################
docker-compose stop
docker-compose run --rm --entrypoint '/bin/sh -c "gosu mysql mysqld --skip-grant-tables & sleep 10 && mysql -hlocalhost -uroot && exit 0"' mysql-mailcow
FLUSH PRIVILEGES;
CREATE USER 'krocka' IDENTIFIED BY 'miso62krocka';
GRANT ALL PRIVILEGES ON *.* TO krocka;
FLUSH PRIVILEGES;
docker-compose start

apt install mariadb-client
mysql -h 127.0.0.1 -P 13306 -u krocka --password=miso62krocka

# nginx + php ##############################################
https://www.digitalocean.com/community/tutorials/how-to-install-linux-nginx-mysql-php-lemp-stack-on-ubuntu-20-04
apt install nginx php-fpm php-mysql zip libphp-phpmailer

/etc/nginx/nginx.conf
client_max_body_size 200M;

/var/www/...
sudo mkdir /var/www/...
sudo chown -R www-data:www-data /var/www/...

systemctl enable php7.4-fpm
systemctl start php7.4-fpm
systemctl status php7.4-fpm

/etc/php/7.4/fpm/php.ini
zend.multibyte = On,
upload_max_filesize = 20M
post_max_size = 20M

# firewall ufw #############################################
ufw app list
ufw status
ufw allow Nginx HTTP
ufw allow Nginx HTTPS
ufw allow OpenSSH

curl -4 icanhazip.com
curl -6 icanhazip.com

# phpMyAdmin ###############################################
https://www.itzgeek.com/post/how-to-install-phpmyadmin-with-nginx-on-ubuntu-20-04/
wget https://files.phpmyadmin.net/phpMyAdmin/5.0.4/phpMyAdmin-5.0.4-all-languages.tar.gz
tar -zxvf phpMyAdmin-5.0.4-all-languages.tar.gz
sudo cp -pr config.sample.inc.php config.inc.php
nano config.inc.php

phpMyAdmin / PHPmYaDMIN

$cfg['blowfish_secret'] =
$cfg['Servers'][$i]['controluser'] = 'krocka';
$cfg['Servers'][$i]['controlpass'] = 'miso62krocka';

$cfg['Servers'][$i]['pmadb'] = 'phpmyadmin';
... unkomentieren

cat sql/create_tables.sql | mysql

sudo mysql
FLUSH PRIVILEGES;
CREATE USER 'krocka' IDENTIFIED BY 'miso62krocka';
GRANT ALL PRIVILEGES ON *.* TO krocka;
FLUSH PRIVILEGES;
exit

sudo nano /etc/nginx/conf.d/phpMyAdmin.conf

server {
   listen 80;
   server_name phpmyadmin.koegler-reisedienst.de;
   root /usr/share/phpMyAdmin;

   location / {
      index index.php;
   }

## Images and static content is treated different
   location ~* ^.+.(jpg|jpeg|gif|css|png|js|ico|xml)$ {
      access_log off;
      expires 30d;
   }

   location ~ /\.ht {
      deny all;
   }

   location ~ /(libraries|setup/frames|setup/libs) {
      deny all;
      return 404;
   }

   location ~ \.php$ {
      include /etc/nginx/fastcgi_params;
      fastcgi_pass 127.0.0.1:9000;
      fastcgi_index index.php;
      fastcgi_param SCRIPT_FILENAME /usr/share/phpMyAdmin$fastcgi_script_name;
   }
}

sudo mkdir /usr/share/phpMyAdmin/tmp
sudo chmod 777 /usr/share/phpMyAdmin/tmp
sudo chown -R www-data:www-data /usr/share/phpMyAdmin
systemctl restart nginx
systemctl restart php7.4-fpm

# krocka.de + koegler-reisedienst.de #######################
gleich...

# db.koegler-reisedienst.de ################################
/var/www/db.koegler-reisedienst.de/
  /data
  /html

MySQL

Mailer:
https://github.com/PHPMailer/PHPMailer/archive/master.zip
unpack in include_path => /usr/share/php

/var/www/usr => scripts

trigger:
ext : /var/www/db.koegler-reisedienst.de/data/
chown -R www-data:www-data * 

udf: sys_exec

apt install inotify-tools

cron:

DB:
DROP TRIGGER IF EXISTS `ext`;CREATE DEFINER=`root`@`localhost`
TRIGGER `ext` AFTER DELETE ON `ext` FOR EACH ROW
CALL bermuda.sys_exec(concat("/bin/rm -f /var/www/db.koegler-reisedienst.de/data/",OLD.tab,"/",
CONCAT_WS(".", LPAD(OLD.mid,6,"0"), LPAD(OLD.id,6,"0"),OLD.ext))) 

ALTER TABLE `database`.`sys` DROP INDEX `name`, ADD
UNIQUE `name` (`name`, `dbuser`, `fun`, `sign`) USING BTREE; 

# bermuda ##################################################
procedure archive <-> sys_exec => archive.php

apt install hp2xx html2ps imagemagick

MySQL:
bermuda_xxxxx + bermuda_temp DB: #########
ALTER TABLE `database`.`sys` DROP INDEX `name`, ADD
UNIQUE `name` (`name`, `dbuser`, `fun`, `sign`) USING BTREE;

trigger:
docu
plan

bermuda DB: ###############
manuell!!
ALTER TABLE `database`.`sys` DROP INDEX `name`, ADD
UNIQUE `name` (`name`, `dbuser`, `fun`, `sign`) USING BTREE; 

DROP PROCEDURE `sys_exec`; CREATE DEFINER=`krocka`@`%`
PROCEDURE `sys_exec`(IN `cmd` VARCHAR(254)) NOT DETERMINISTIC
NO SQL SQL SECURITY DEFINER SELECT cmd INTO OUTFILE "/tmp/sys_exec/database"

DROP TRIGGER IF EXISTS `del_project`;CREATE DEFINER=`root`@`localhost`
TRIGGER `del_project` AFTER DELETE ON `project` FOR EACH ROW
BEGIN INSERT INTO log (event,project) VALUES ("DEL PROJECT", OLD.project);
CALL bermuda.sys_exec(CONCAT("echo 'DROP DATABASE bermuda_", LPAD(OLD.id, 5, "0"), "' | mysql -ukrocka --password=krocka"));
CALL bermuda.sys_exec(CONCAT("/bin/rm -f -r /var/www/bermuda.goip.de/data/bermuda_", LPAD(OLD.id,5,"0")));
END

DROP TRIGGER IF EXISTS `ins_project`;CREATE DEFINER=`root`@`localhost`
TRIGGER `ins_project` AFTER INSERT ON `project` FOR EACH ROW
BEGIN INSERT INTO log (dbuser,event,project)
VALUES(NEW.admin,"INS PROJECT", NEW.project);
CALL bermuda.sys_exec(CONCAT("/bin/mkdir -m 0777 -p /var/www/bermuda.goip.de/data/bermuda_", LPAD(NEW.id,5,"0")));
CALL bermuda.sys_exec(CONCAT("/bin/mkdir -m 0777 -p /var/www/bermuda.goip.de/data/bermuda_", LPAD(NEW.id,5,"0"),"/plan"));
CALL bermuda.sys_exec(CONCAT("/bin/mkdir -m 0777 -p /var/www/bermuda.goip.de/data/bermuda_", LPAD(NEW.id,5,"0"),"/doku"));
CALL bermuda.sys_exec(CONCAT("/var/www/usr/bermuda/dbcopy.sh bermuda_temp bermuda_", LPAD(NEW.id,5,"0")));
END

# krocka.goip.de ###########################################
apt install npm sqlite

# cerbot ###################################################
certbot --nginx -d bermuda.goip.de -d bvg-bau.net -d www.bvg-bau.net -d koegler-reisedienst.de -d www.koegler-reisedienst.de -d mail.koegler-reisedienst.de -d krocka.de -d www.krocka.de -d krocka.goip.de
