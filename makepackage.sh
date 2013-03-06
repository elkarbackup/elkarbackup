#!/bin/bash

#
# prepare source for build
#

mkdir .debian
cp -al debian/* .debian
if [ ! -d .debian/usr/share/elkarbackup ]
then
    if [ "$FROM_SVN" != "" ]
    then
        mkdir -p .debian/usr/share/
        svn export https://intranet.binovo.es/svn/tknika-backups/trunk .debian/usr/share/elkarbackup
        pushd .debian/usr/share/elkarbackup
        composer install
        popd
    else
        php app/console assetic:dump
        php app/console cache:clear --env=prod --no-debug
        php app/console cache:clear --env=dev  --no-debug
        mkdir -p .debian/usr/share/elkarbackup
        cp -al * .debian/usr/share/elkarbackup
    fi
fi
# remove uneeded files from copy to package
find .debian -type d -name ".svn" | xargs rm -rf
find .debian -type f -name "*.deb"| xargs rm -rf
find .debian/usr/share/elkarbackup/web/js/dojo-release-1.8.1 -name "*.uncompressed.js"|xargs rm -f
find .debian -name ".git*" -o -name "*~" -o -name "*#*"| xargs rm -rf
rm -rf .debian/usr/share/elkarbackup/app/{cache,logs,sessions} .debian/usr/share/elkarbackup/backups .debian/usr/share/elkarbackup/debian
# fix some files so that lintian doesn't complain (so much)
find .debian -name "*.png" -o -name "*.gif" -o -name "*.php" -o -name "README" -o -name "*.md" -o -name "*.dist" -o -name "*.ini" -o -name "*.yml" -o -name "*.rst" -o -name "*.xml" -o -name "*.js"| xargs chmod a-x
find .debian/usr/share/elkarbackup/web/js/dojo-release-1.8.1 -type f|xargs chmod a-x
sed -i '1c#!/bin/bash' .debian/usr/share/elkarbackup/vendor/swiftmailer/swiftmailer/test-suite/lib/simpletest/packages/build_tarball.sh
# ensure the packaged versions uses only the release environment
rm .debian/usr/share/elkarbackup/web/app_dev.php
sed -i 's/app_dev/app/' .debian/usr/share/elkarbackup/web/.htaccess
# setup cache, session and log directories in var
mkdir -p .debian/var/cache/elkarbackup
mkdir -p .debian/var/log/elkarbackup
mkdir -p .debian/var/lib/elkarbackup/sessions
ln -s /var/cache/elkarbackup        .debian/usr/share/elkarbackup/app/cache
ln -s /var/log/elkarbackup          .debian/usr/share/elkarbackup/app/logs
ln -s /var/lib/elkarbackup/sessions .debian/usr/share/elkarbackup/app/sessions
# setup configuraiton in /etc
mv .debian/usr/share/elkarbackup/app/config .debian/etc/elkarbackup
ln -s  /etc/elkarbackup .debian/usr/share/elkarbackup/app/config
# put copyright notices and changelog in its place
mkdir -p .debian/usr/share/doc/elkarbackup
cp -al changelog changelog.Debian copyright .debian/usr/share/doc/elkarbackup
gzip -f --best .debian/usr/share/doc/elkarbackup/changelog
gzip -f --best .debian/usr/share/doc/elkarbackup/changelog.Debian
# ensure directory permissions are right
find .debian -type d | xargs chmod 755
# set initial values for parametres
sed -i 's#tmp_dir:.*#tmp_dir: /tmp#'                                 .debian/etc/elkarbackup/parameters.yml
sed -i 's#database_name: tknikabackups#database_name: elkarbackup#'  .debian/etc/elkarbackup/parameters.yml
sed -i 's#backup_dir:.*#backup_dir: /var/spool/elkarbackup/backups#' .debian/etc/elkarbackup/parameters.yml
sed -i 's#upload_dir:.*#upload_dir: /var/spool/elkarbackup/uploads#' .debian/etc/elkarbackup/parameters.yml
sed -i 's#mailer_transport:.*#mailer_transport: smtp#'               .debian/etc/elkarbackup/parameters.yml
sed -i 's#mailer_user:.*#mailer_user: #'                             .debian/etc/elkarbackup/parameters.yml
sed -i 's#mailer_password:.*#mailer_password: #'                     .debian/etc/elkarbackup/parameters.yml
sed -i 's#mailer_host:.*#mailer_host: localhost#'                    .debian/etc/elkarbackup/parameters.yml
# use prod environment in console by default
sed -i "s#'dev'#'prod'#"                                             .debian/usr/share/elkarbackup/app/console
chmod a+x .debian/usr/share/elkarbackup/app/console
VERSION=$(cat debian/DEBIAN/control | grep 'Version' | sed -e 's/Version: //' -e 's/ *//')
mkdir -p .debian/var/spool/elkarbackup/backups
mkdir -p .debian/var/spool/elkarbackup/uploads

#
# build an verify
#
fakeroot dpkg-deb --build .debian elkarbackup_${VERSION}_all.deb
echo Package created

lintian elkarbackup_${VERSION}_all.deb | tee lintian.log | egrep '^E'
