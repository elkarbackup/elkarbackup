#!/bin/bash
aptitude install acl
setfacl -R  -m u:www-data:rwx -m u:backup:rwx /srv/web/tknikabackups-devel/app/{cache,logs}
setfacl -dR -m u:www-data:rwx -m u:backup:rwx /srv/web/tknikabackups-devel/app/{cache,logs}
cp /srv/web/tknikabackups-devel/bnvbackups /etc/cron.d/
cp /srv/web/tknikabackups-devel/bnvbackuptick /usr/bin/



chown -R .backup /srv/web/tknikabackups-devel/app/cache/{dev,prod}/annotations
chmod g+ws /srv/web/tknikabackups-devel/app/cache/{dev,prod}/annotations
