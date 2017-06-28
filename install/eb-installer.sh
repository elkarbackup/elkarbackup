#! /bin/bash -e
#
# Usage:
# ./eb-installer.sh [OPTIONS]
# [-f customconfigfile] [-h dbhost] [-u dbuser]
# [-U dbadminuser] [-p dbpass] [-P dbadminpass]
# [-n dbname] [-v version] [-y] [-d] [-I]

# Config

INSTALLER_VERSION="0.1.96"
EB_PATH=/usr/local/elkarbackup
TMP_PATH=/tmp

if [ -f "/etc/debian_version" ];then
  # Debian based distros
  APACHE_USER=www-data
  APACHE_GROUP=www-data
else
  # Fedora etc
  APACHE_USER=apache
  APACHE_GROUP=apache
fi

# Functions

function debug_info
{
  if [[ -n "$debug" ]]; then
    echo -e "\n***************************"
    echo -e "* DEBUG"
    echo -e "***************************"

    echo "Installer version: $INSTALLER_VERSION"
    echo "ElkarBackup version: $version"
    [ -n "$customconfigfile" ] && echo "Config file: $customconfigfile"
    [ -n "$dbhost" ] && echo "DB host: $dbhost"
    [ -n "$dbuser" ] && echo "DB user: $dbuser"
    [ -n "$dbpass" ] && echo "DB pass: $dbpass"
    [ -n "$dbname" ] && echo "DB name: $dbname"
    [ -n "$assumeyes" ] && echo "Assume yes enabled"
    [ -n "$debug" ] && echo "Debug mode enabled"
    echo "***************************"
  fi
}

function ask_confirmation
{
  # Ask confirmation (not in assumeyes mode)
  if [[ -z "$assumeyes" ]];then
    echo -e "\nThis program will install ElkarBackup \"${version}\""
    while true; do
        read -p "Do you want to continue? " yn
        case $yn in
            [Yy]* ) break;;
            [Nn]* ) exit;;
            * ) echo "Please answer yes or no.";;
        esac
    done
  fi
}

function ask_dbconfig
{
  # Interactive mode: ask dbhost, dbuser, dbpass
  if [[ -z "$dbhost" ]];then
    echo -e "\nDatabase server host address, followed by [ENTER]"
    while true; do
      read -e -p "DB server: " -i "localhost" dbhost
      [ "$dbhost" ] && break
    done
  fi

  if [[ -z "$dbuser" ]];then
    echo -e "\nDatabase elkarbackup user, followed by [ENTER]"
    while true; do
      read -e -p "User: " -i elkarbackup dbuser
      [ "$dbuser" ] && break
    done
  fi

  if [[ -z "$dbpass" ]];then
    echo -e "\nDatabase password for elkarbackup user, followed by [ENTER]"
    while true; do
      read -s -p "Password: " dbpass
      [ "$dbpass" ] && break
    done
  fi

  # Save the correct values in the "param" array
  param["database_host"]=$dbhost
  param["database_name"]=$dbname
  param["database_user"]=$dbuser
  param["database_password"]=$dbpass
}

function ask_dbconfig_more
{
  # Interactive mode: ask dbhost, dbuser, dbpass
  if [[ -z "$dbadminuser" ]];then
    echo -e "\nDatabase server admin user, followed by [ENTER]"
    while true; do
      read -e -p "DB admin user: " -i "root" dbadminuser
      [ "$dbadminuser" ] && break
    done
  fi

  # Interactive mode: ask dbhost, dbuser, dbpass
  if [[ -z "$dbadminpass" ]];then
    echo -e "\nDatabase admin pasword, followed by [ENTER]"
    while true; do
      read -s -p "DB admin password: " dbadminpass
      [ "$dbadminpass" ] && break
    done
  fi
}

function check_webserver ()
{
  echo -e "\n\nChecking installed web servers:"
  error=true
  for app in "$@"
  do
    which $app > /dev/null 2> /dev/null
    if [ "$?" == "0" ];then
      printf "\n - %-20s\e[1;32m[OK]\e[0m" "${app}"
      error=false
    else
      printf "\n - %-20s[NOT FOUND]" "${app}"
    fi
  done
  printf "\n"

  if $error; then
    return 1
  else
    return 0
  fi
}

function check_deps ()
{
  echo -e "\n\nChecking required dependencies:"
  error=false
  for app in "$@"
  do
    which $app > /dev/null 2> /dev/null
    if [ "$?" == "0" ];then
      printf "\n - %-20s\e[1;32m[OK]\e[0m" "${app}"
    else
      printf "\n - %-20s\e[1;31m[ERROR]\e[0m" "${app}"
      error=true
    fi
  done
  printf "\n"

  if $error; then
    return 1
  else
    return 0
  fi
}

function check_php_mods ()
{
  echo -e "\n\nChecking required PHP modules:"
  error=false
  for mod in "$@"
  do
    php -m | grep -w $mod > /dev/null
    if [ "$?" == "0" ];then
      printf "\n - %-20s\e[1;32m[OK]\e[0m" "${mod}"
    else
      printf "\n - %-20s\e[1;31m[ERROR]\e[0m" "${mod}"
      error=true
    fi
  done
  printf "\n"

  if $error; then
    return 1
  else
    return 0
  fi
}

function download
{
  echo -e "\n\nDownloading ElkarBackup $version...\n"
  base_url="https://api.github.com/repos/elkarbackup/elkarbackup/releases"

  if [ "$version" == "dev" ];then
    git clone https://github.com/elkarbackup/elkarbackup.git $EB_PATH && return 0 || return 1
  fi

  if [ "$version" == "latest" ];then
    api_url="${base_url}/latest"
  else
    api_url="${base_url}/tags/$version"
  fi

  download_url=$(curl -s {$api_url}|grep "tarball_url"|cut -d '"' -f 4)
  if  [ -z "$download_url" ];then
    echo "ERROR: cannot find version $version"
    false
  else
    curl -L -o $TMP_PATH/elkarbackup.tar.gz ${download_url}
    if [ ! -s "$TMP_PATH/elkarbackup.tar.gz" ];then
      echo "Downloaded file is empty"
      false
    fi
  fi
}

function extract
{
  # Delete previous EB_PATH (if exists)
  if [ -d "$EB_PATH" ];then
    if [ -n "$assumeyes" ];then
      rm -fR $EB_PATH
    else
      while true; do
        echo -e "\n"
        read -p "Directory $EB_PATH already exists. Do you want to rewrite it? " yn
        case $yn in
          [Yy]* ) rm -fR $EB_PATH && break;;
          [Nn]* ) echo "Installation canceled" && exit;;
          * ) echo "Please answer yes or no.";;
        esac
      done
    fi
  fi

  # Extraction
  mkdir $EB_PATH
  printf "\nExtracting package in %s..." $EB_PATH
  tar xzf $TMP_PATH/elkarbackup.tar.gz -C $EB_PATH --strip-components=1 && rm $TMP_PATH/elkarbackup.tar.gz || exit 1
  printf " \e[1;32m[OK]\e[0m\n"
}

function create_directories
{
  configfile="$EB_PATH/app/config/parameters.yml.dist"
  username="elkarbackup"

  # Create upload directory and set up permissions
  upload_dir=`sed -n 's/^[ \t]*upload_dir:[ \t]*\([^ #\t]*\).*/\1/p' $configfile`
  mkdir -p $upload_dir && chown -R $APACHE_USER:$APACHE_GROUP $upload_dir
}

function setup_parameters ()
{
  key=$1
  value=$2
  if [[ ! -z "$value" ]]; then
    printf "\nSetting up key %s in parameters.yml..." $key
    sed -i "s#${key}:.*#${key}: ${value}#" $EB_PATH/app/config/parameters.yml.dist
    printf " \e[1;32m[OK]\e[0m"
  fi
}

function check_db
{
  # Get parameters from configuration file
  configfile="$EB_PATH/app/config/parameters.yml.dist"
  dbhost=`sed -n 's/^[ \t]*database_host:[ \t]*\([^ #\t]*\).*/\1/p' $configfile`
  dbname=`sed -n 's/^[ \t]*database_name:[ \t]*\([^ #\t]*\).*/\1/p' $configfile`
  dbusername=`sed -n 's/^[ \t]*database_user:[ \t]*\([^ #\t]*\).*/\1/p' $configfile`
  dbuserpassword=`sed -n 's/^[ \t]*database_password:[ \t]*\([^ #\t]*\).*/\1/p' $configfile`

  # Does the database already exist?
  if mysql -u"$dbusername" -p"$dbuserpassword" -h"$dbhost" "$dbname" </dev/null &>/dev/null
  then
    return 0
  else
    # Database elkarbackup not found
    return 1
  fi
}

function create_db
{
  # Can we connect to the DB server with admin privileges?
  # First maybe we need to ask admin user and password...
  ask_dbconfig_more

  # Exit if we don't have admin DB user
  if [[ -z $dbadminuser ]];then
    echo "ERROR: empty DB admin user"
    return 1
  fi

  if mysql -u"$dbadminuser" -p"$dbadminpass" -h"$dbhost" </dev/null &>/dev/null
  then
    #echo "Attempting to create DB $dbname in $dbhost"
    echo "CREATE DATABASE IF NOT EXISTS $dbname DEFAULT CHARACTER SET utf8;" | mysql -u"$dbadminuser" -p"$dbadminpass" -h"$dbhost"
    return 0
  else
    echo "ERROR connecting with the database. Incorrect configuration parameters."
    return 1
  fi
}

function create_dbuser
{
  if [ "$dbhost" = localhost ]
  then
      user="'$dbusername'@localhost"
  else
      user="'$dbusername'"
  fi

  #echo "Attempting to create user $dbusername in $dbhost"
  echo "GRANT ALL ON $dbname.* TO $user IDENTIFIED BY '$dbuserpassword';" | mysql -u"$dbadminuser" -p"$dbadminpass" -h"$dbhost" || true
}

function create_elkarbackup_user
{
  username="elkarbackup"
  groupname="elkarbackup"
  userhome=$EB_PATH/keys

  mkdir -p $EB_PATH/keys
  if test x`grep $username /etc/passwd` = x
  then
    # Debian based distros
    if [ -f "/etc/debian_version" ];then
      adduser --system --home /var/lib/elkarbackup --shell /bin/bash --group $username
    else
      # Fedora and others
      groupadd $groupname
      gid=`getent group $groupname | cut -d: -f3`
      adduser -r --gid $gid --home $userhome --shell /bin/bash $username
    fi
    chown -R $username:$groupname $userhome &>/dev/null
  fi
  if [ ! -f $userhome/.ssh/id_rsa ]
  then
      mkdir $userhome/.ssh
      ssh-keygen -t rsa -N '' -C 'Automatically generated key for elkarbackup.' -f $userhome/.ssh/id_rsa
      sed -i "s#public_key:.*#public_key: $userhome/.ssh/id_rsa.pub#" $EB_PATH/app/config/parameters.yml
      chown -R $username:$username $userhome
  fi
}

function bootstrap
{
  # Delete old parameters.yml
  rm $EB_PATH/app/config/parameters.yml
  echo -e "\n"
  if [[ ! -n $assumeyes ]]; then
    read -n1 -r -p "Press any key to continue..." key
  fi
  echo -e "\n"
  cd $EB_PATH
  ./bootstrap.sh

  # Correct parameters.yml permission
  username="elkarbackup"
  configfile=$EB_PATH/app/config/parameters.yml
  chown $APACHE_USER:$APACHE_GROUP $configfile

  # Correct backup parent directory permissions (usually: /var/spool/elkarbackup)
  backup_dir=`sed -n 's/^[ \t]*backup_dir:[ \t]*\([^ #\t]*\).*/\1/p' $configfile`
  backup_parent_dir=${backup_dir%/*}
  chown $username:$username $backup_parent_dir
}

function update_db
{
  # Delete cache content
  rm -fR $EB_PATH/app/cache/*

  # Update DB (delete a buggy diff)
  rm -f $EB_PATH/app/DoctrineMigrations/Version20130306101349.php
  php $EB_PATH/app/console doctrine:migrations:migrate --no-interaction >/dev/null || return 1
  echo -e "\nDB updated"
}

function create_root_user
{
  php $EB_PATH/app/console elkarbackup:create_admin >/dev/null || return 1
  echo -e "\nElkarbackup admin user created"
}

function clear_cache
{
  php $EB_PATH/app/console cache:clear >/dev/null || return 1
}

function dump_assets
{
  php $EB_PATH/app/console assetic:dump >/dev/null || return 1
}

function invalidate_sessions
{
  rm -rf $EB_PATH/app/sessions/*
}

function configure_apache
{
  # apache = apache2 (Debian) or httpd (Fedora)?
  which apache2 > /dev/null 2> /dev/null && apache=apache2 || apache=httpd

  # Templates
  ebsite=$EB_PATH/debian/etc/apache2/sites-available/elkarbackup.conf
  ebsitessl=$EB_PATH/debian/etc/apache2/sites-available/elkarbackup-ssl.conf
  ebconf=$EB_PATH/debian/etc/apache2/conf-available/elkarbackup.conf

  # Debian: Enable apache mods ssl and rewrite
  if which a2enmod >/dev/null 2>/dev/null; then
    a2enmod rewrite
    a2enmod ssl
  fi

  # Copy configuration
  if [ -d "/etc/apache2" ];then
    # Debian
    cp $ebsite /etc/apache2/sites-available/elkarbackup.conf
    cp $ebsitessl /etc/apache2/sites-available/elkarbackup-ssl.conf
    cp $ebconf /etc/apache2/conf-available/elkarbackup.conf
    # Change directory from template
    sed -i 's~/usr/share/elkarbackup/web~'$EB_PATH'/web~' /etc/apache2/conf-available/elkarbackup.conf || return 1
    sed -i 's~/usr/share/elkarbackup/web~'$EB_PATH'/web~' /etc/apache2/sites-available/elkarbackup.conf || return 1
    sed -i 's~/usr/share/elkarbackup/web~'$EB_PATH'/web~' /etc/apache2/sites-available/elkarbackup-ssl.conf || return 1
  elif [ -f "/etc/httpd/conf/httpd.conf" ];then
    # BETA: Fedora
    cp $ebconf /etc/httpd/conf.d/
    sed -i 's~/usr/share/elkarbackup/web~'$EB_PATH'/web~' /etc/httpd/conf.d/elkarbackup.conf || return 1
    
    # SELinux
    chcon -t httpd_sys_rw_content_t $EB_PATH/app/cache/ -R
    chcon -t httpd_sys_rw_content_t $EB_PATH/app/sessions/ -R
    chcon -t httpd_sys_rw_content_t $EB_PATH/app/logs/ -R
    
    $apache -S || return 1
  else
    echo "ERROR: Apache2 not found"
    return 1
  fi


  # Restart apache
  if which a2enconf >/dev/null 2>/dev/null; then
    a2enconf elkarbackup
    a2ensite elkarbackup
    a2ensite elkarbackup-ssl
  fi
  service $apache restart || return 1
}

function configure_cron
{
  username="elkarbackup"
  cronfiledest=/etc/cron.d/elkarbackup
  cronfileorig=$EB_PATH/debian/etc/cron.d/elkarbackup

  cp $cronfileorig $cronfiledest
  sed -i "s/www-data/${username}/" $cronfiledest
  sed -i "s@/usr/share/elkarbackup@${EB_PATH}@" $cronfiledest
}

function usage
{
  printf "Usage: ./eb-installer.sh [OPTIONS]\n"
  printf "  %-5s %s\n" "-v" "Select ElkarBackup version you want to install"
  printf "  %-5s %s\n" "-f" "Path to a customized parameters.yml configuration file"
  printf "  %-5s %s\n" "-h" "Database server IP address or hostname"
  printf "  %-5s %s\n" "-u" "User for login to connect to the database"
  printf "  %-5s %s\n" "-U" "Admin user to create the new database"
  printf "  %-5s %s\n" "-p" "Password to use when connecting to server"
  printf "  %-5s %s\n" "-P" "Admin password to use when connecting with admin user"
  printf "  %-5s %s\n" "-n" "Database name"
  printf "  %-5s %s\n" "-y" "Assume yes mode will omit any question"
  printf "  %-5s %s\n" "-d" "DEBUG mode"
  printf "  %-5s %s\n" "-I" "Display this help and exit"

}



# Main

while getopts ":v:h:u:U:p:P:n:f:dyI" opt; do
  case $opt in
    v)
      version=$OPTARG
      ;;
    f)
      customconfigfile=$OPTARG
      ;;
    h)
      dbhost=$OPTARG
      ;;
    u)
      dbuser=$OPTARG
      ;;
    U)
      dbadminuser=$OPTARG
      ;;
    p)
      dbpass=$OPTARG
      ;;
    P)
      dbadminpass=$OPTARG
      ;;
    n)
      dbname=$OPTARG
      ;;
    y)
      assumeyes=1
      ;;
    d)
      debug=1
      ;;
    I)
      usage
      exit 1
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      usage
      exit 1
      ;;
    :)
      echo "Option -$OPTARG requires an argument" >&2
      usage
      exit 1
      ;;
  esac
done

# Read environment variables (TODO)
envars=$(compgen -A variable |grep SYMFONY)
for envar in $envars;
do
  echo $envar;
done

debug_info

# Version is optional. Default: latest
version=${version:-"latest"}

# Parameter array definition (values assigned in ask_dbonfig)
declare -A param

# Required params in assume yes mode: dbhost, dbuser, dbpass
# Not if we have a customconfigfile as parameter (paramaters.yml)
# If Symfony is able to read environment variables directly
# this wouldnt be necessary. More info: https://github.com/symfony/symfony/pull/19681
if [[ ( -n "$assumeyes" ) && ( -z "$customconfigfile" ) ]];then
  echo -e "\n"
  [ "$dbhost" ] || { echo "ERROR: DB host (-s) required in assume yes mode"; exit 1; };
  [ "$dbuser" ] || { echo "ERROR: DB user (-u) required in assume yes mode"; exit 1; };
  [ "$dbpass" ] || { echo "ERROR: DB pass (-p) required in assume yes mode"; exit 1; };
fi

echo -e "\n****************************"
echo -e "\n* ELKARBACKUP INSTALLATION *"
echo -e "\n****************************"

ask_confirmation
check_webserver apachectl nginx ||
  { echo "ERROR: install a webserver (currently only Apache2 supported)"; exit 1; };
check_deps php mysql rsnapshot setfacl bzip2 zip ||
  { echo "ERROR: unmet dependencies"; exit 1; };
check_php_mods pdo_mysql xml ||
  { echo "ERROR: install required php extensions"; exit 1; };
download ||
  { echo "ERROR: download failed"; exit 1; };
# Extract the download package (not if we downloaded dev version using git)
if [ "$version" != "dev" ];then
  extract ||
    { echo "ERROR: cannot write on $EB_PATH"; exit 1; };
fi
create_directories

# If we don't have a $customconfigfile, some db configuration parameters needed
if [[ -z "$customconfigfile" ]];then
  ask_dbconfig
  # Configure new params in parameters.yml
  for key in ${!param[@]}; do
    setup_parameters ${key} ${param[${key}]} ||
    { echo "ERROR: cannot change parameter ${key}"; exit 1; };
  done
else
  # If we have a config file, let's use it
  if [[ ! -z "$customconfigfile" && -f "$customconfigfile" ]];then
    cp $customconfigfile $EB_PATH/app/config/parameters.yml.dist
  else
    echo "ERROR: cannot found configuration file: $customconfigfile"
    exit 1
  fi
fi

if check_db; then
  echo -e "\n\nFound an existing database. Updating... "
else
  echo -e "\n\nCreating database"
  if create_db; then
    printf "\t[OK]"
  else
    printf "\t[ERROR]"
  fi

  echo -e "\nCreating database user"
  if create_dbuser; then
    printf "\t[OK]"
  else
    printf "\t[ERROR]"
  fi
fi

create_elkarbackup_user ||
  { echo "ERROR: error creating elkarbackup user"; exit 1; };
bootstrap ||
  { echo "ERROR: error bootstraping elkarbackup"; exit 1; };
update_db ||
  { echo "ERROR: error updating database"; exit 1; };
create_root_user ||
  { echo "ERROR: error creating elkarbackup root user"; exit 1; };
clear_cache ||
  { echo "ERROR: error clearing symfony cache"; exit 1; };
dump_assets ||
  { echo "ERROR: error dumping symfony assets"; exit 1; };
invalidate_sessions ||
  { echo "ERROR: error invalidating symfony sessions"; exit 1; };
configure_apache ||
  { echo "ERROR: error configuring apache"; exit 1; };
configure_cron ||
  { echo "ERROR: cannot add cron configuration"; exit 1; };

echo -e "\nElkarBackup installed succesfully."
exit 0
