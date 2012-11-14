#!/bin/bash

# # copy source for build
if [! -d .debian/usr/share/tknikabackups ]
then
    mkdir -p .debian/usr/share/
    svn export https://intranet.binovo.es/svn/tknika-backups/trunk .debian/usr/share/tknikabackups
    pushd .debian/usr/share/tknikabackups
    composer install
    popd
fi
find .debian/usr/share -type d -name ".svn"| xargs rm -rf
find .debian/usr/share -name ".git*" -o -name "*~"| xargs rm -rf
find .debian/usr/share -name "*.png" -o -name "*.gif" -o -name "*.php" -o -name "README" -o -name "*.md" -o -name "*.dist" -o -name "*.ini" -o -name "*.yml" -o -name "*.rst" -o -name "*.xml" -o -name "*.js"| xargs chmod a-x
find .debian/usr/share/tknikabackups/web/js/dojo-release-1.8.1 -type f|xargs chmod a-x
find .debian/usr/share/tknikabackups/web/js/dojo-release-1.8.1 -name "*.uncompressed.js"|xargs rm -f
rm -rf .debian/usr/share/tknikabackups/app/{cache,logs}
mkdir -p .debian/var/cache/tknikabackups
mkdir -p .debian/var/log/tknikabackups
ln -s /var/cache/tknikabackups .debian/usr/share/tknikabackups/app/cache
ln -s /var/log/tknikabackups   .debian/usr/share/tknikabackups/app/logs
sed -i '1c#!/bin/bash' .debian/usr/share/tknikabackups/vendor/swiftmailer/swiftmailer/test-suite/lib/simpletest/packages/build_tarball.sh
sed -i 's/app_dev/app/' .debian/usr/share/tknikabackups/web/.htaccess
rm .debian/usr/share/tknikabackups/web/app_dev.php

# put copyright notices and changelog in its place
mkdir -p .debian/usr/share/doc/tknikabackups
cp changelog changelog.Debian copyright .debian/usr/share/doc/tknikabackups
gzip -f --best .debian/usr/share/doc/tknikabackups/changelog
gzip -f --best .debian/usr/share/doc/tknikabackups/changelog.Debian

# ensure directory permissions are right
find .debian -type d | xargs chmod 755

# build an verify
fakeroot dpkg-deb --build .debian Tknikabackups_1.0_all.deb

lintian Tknikabackups_1.0_all.deb | tee lintian.log
