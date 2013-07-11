Elkarbackup
===========

This project is a web interface for the rsnapshot backup program.

##Supported languages:
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

1. Install the required packages: `sudo apt-get install debconf php5 php5-cli rsnapshot mysql-server php5-mysql acl bzip2`
2. Adding the repository: (todo)


# How to collaborate

## Download and build the package

1. Download last version: `git clone git@github.com:elkarbackup/elkarbackup.git`
2. Build Debian package from the source:

- Bootstrap the application: this will install composer and dojo if necesary.

    ./bootstrap.sh

- Create the package

    ./makepackage.sh
