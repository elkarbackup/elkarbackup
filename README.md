Elkarbackup
===========

This project is a web interface for the rsnapshot backup program.

- [Installation&User Guide (Basque)] (https://github.com/elkarbackup/elkarbackup/blob/master/docs/Elkarbackup-Eskuliburua-eu-zuzenduta-2b.pdf?raw=true)
- [Installation&user Guide (Spanish)] (https://github.com/elkarbackup/elkarbackup/blob/master/docs/Elkarbackup-Eskuliburua-es-zuzenduta-2b.pdf?raw=true)

###Supported languages:
- English
- Spanish
- Basque


#Do you want to try it?

Well, you have two options:

##Download a ready-to-use VM

- IMG (raw) file for KVM-Proxmox (64-bit): http://ftp.tknika.net/elkarbackup/ElkarBackupServerBase2GB1.0.9_64b.img (2,1 G)
- IMG (raw) file for KVM-Proxmox (32-bit): (todo)
- VMDK file for VMWare (64-bit): http://ftp.tknika.net/elkarbackup/ElkarBackupServerBase2GB1.0.9_64b.vmdk (1,4 G)
- VMDK file for VMWare (32-bit): (todo)

##Or download and install the Debian packages

1. Adding GPG key: `wget -O - http://elkarbackup.org/apt/archive.gpg.key | sudo apt-key add -`
2. Edit your '/etc/apt/sources.list' and add this line: `deb http://elkarbackup.org/apt/debian squeeze main`
3. Install the required packages: `sudo apt-get install debconf php5 php5-cli rsnapshot mysql-server php5-mysql acl bzip2`
4. Install elkarbackup: `sudo apt-get install elkarbackup`


# How to collaborate

## Download and build the package

1. Download latest version: `git clone git@github.com:elkarbackup/elkarbackup.git`
2. Build Debian package from the source:

- Bootstrap the application: this will install composer and dojo if necesary.

    ./bootstrap.sh

- Create the package

    ./makepackage.sh
