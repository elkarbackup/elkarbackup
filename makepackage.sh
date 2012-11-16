#!/bin/bash

#
# prepare source for build
#

mkdir .debian
cp -al debian/* .debian
if [ ! -d .debian/usr/share/tknikabackups ]
then
    if [ "$FROM_SVN" != "" ]
    then
        mkdir -p .debian/usr/share/
        svn export https://intranet.binovo.es/svn/tknika-backups/trunk .debian/usr/share/tknikabackups
        pushd .debian/usr/share/tknikabackups
        composer install
        popd
    else
        php app/console cache:clear --env=prod --no-debug
        php app/console cache:clear --env=dev  --no-debug
        mkdir -p .debian/usr/share/tknikabackups
        cp -al * .debian/usr/share/tknikabackups
    fi
fi
# remove uneeded files from copy to package
find .debian -type d -name ".svn"| xargs rm -rf
find .debian/usr/share/tknikabackups/web/js/dojo-release-1.8.1 -name "*.uncompressed.js"|xargs rm -f
find .debian -name ".git*" -o -name "*~"| xargs rm -rf
rm -rf .debian/usr/share/tknikabackups/app/{cache,logs} .debian/usr/share/tknikabackups/backups .debian/usr/share/tknikabackups/debian
# fix some files so that lintian doesn't complain (so much)
find .debian -name "*.png" -o -name "*.gif" -o -name "*.php" -o -name "README" -o -name "*.md" -o -name "*.dist" -o -name "*.ini" -o -name "*.yml" -o -name "*.rst" -o -name "*.xml" -o -name "*.js"| xargs chmod a-x
find .debian/usr/share/tknikabackups/web/js/dojo-release-1.8.1 -type f|xargs chmod a-x
sed -i '1c#!/bin/bash' .debian/usr/share/tknikabackups/vendor/swiftmailer/swiftmailer/test-suite/lib/simpletest/packages/build_tarball.sh
# ensure the packaged versions uses only the release environment
rm .debian/usr/share/tknikabackups/web/app_dev.php
sed -i 's/app_dev/app/' .debian/usr/share/tknikabackups/web/.htaccess
# setup cache and log directoies in var
mkdir -p .debian/var/cache/tknikabackups
mkdir -p .debian/var/log/tknikabackups
ln -s /var/cache/tknikabackups .debian/usr/share/tknikabackups/app/cache
ln -s /var/log/tknikabackups   .debian/usr/share/tknikabackups/app/logs
# setup configuraiton in /etc
mv .debian/usr/share/tknikabackups/app/config .debian/etc/tknikabackups
ln -s  /etc/tknikabackups .debian/usr/share/tknikabackups/app/config
# put copyright notices and changelog in its place
mkdir -p .debian/usr/share/doc/tknikabackups
cp -al changelog changelog.Debian copyright .debian/usr/share/doc/tknikabackups
gzip -f --best .debian/usr/share/doc/tknikabackups/changelog
gzip -f --best .debian/usr/share/doc/tknikabackups/changelog.Debian
# ensure directory permissions are right
find .debian -type d | xargs chmod 755
# set initial values for parametres
sed -i 's#tmp_dir:.*#tmp_dir: /tmp#'                                   .debian/etc/tknikabackups/parameters.yml
sed -i 's#backup_dir:.*#backup_dir: /var/spool/tknikabackups/backups#' .debian/etc/tknikabackups/parameters.yml
sed -i 's#upload_dir:.*#upload_dir: /var/spool/tknikabackups/uploads#' .debian/etc/tknikabackups/parameters.yml
mkdir -p .debian/var/spool/tknikabackups/backups
mkdir -p .debian/var/spool/tknikabackups/uploads

#
# build an verify
#
fakeroot dpkg-deb --build .debian tknikabackups_1.0_all.deb
echo Package created

lintian tknikabackups_1.0_all.deb | tee lintian.log | egrep '^E'
