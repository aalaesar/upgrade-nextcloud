[![Build Status](https://travis-ci.org/aalaesar/upgrade-nextcloud.svg?branch=master)](https://travis-ci.org/aalaesar/upgrade-nextcloud)

# upgrade-nextcloud

This role upgrades an Nextcloud instance on a debian/Ubuntu server.

The role's main actions are:
- [x] Version comparison
- [x] Nextcloud's instance backup. (db, rootDir, dataDir)
- [x] Upgrade using nextcloud's CLI
- [x] Strengthened files permissions and ownership following Nextcloud recommendations.

based on https://docs.nextcloud.com/server/10/admin_manual/maintenance/manual_upgrade.html
## Requirements
### Ansible version
Ansible 2.0
### Root access
This role requires root access, so either configure it in your inventory files, run it in a playbook with a global `become: yes` or invoke the role in your playbook like:
> playbook.yml:
```YAML
- hosts: dnsserver
  roles:
    - role: aalaesar.upgrade-nextcloud
      become: yes
```

## Role Variables

Role's variables (and their default value):

### Configuration
> Source location will be calculated following channel, version and branch values.

```YAML
nextcloud_channel: "releases"
```
Defines the version channel you want to use for the upgrade.
Available : releases | prereleases | daily | latest
```YAML
nextcloud_version: 10.0.2
```
Specify the version name for channels **releases**, **prereleases** and **daily**. (it may not be numbers at all)
```YAML
nextcloud_branch: "stable"
```
Specify the branch name for **daily** & **latest** channel
```YAML
nextcloud_repository: "https://download.nextcloud.com/server"
```
The Nextcloud's official repository. You may change it if you have the sources somewhere else.
### Main configuration
```YAML
nextcloud_websrv: "apache"
```
The http server used by nextcloud. Available values are: **apache** or **nginx**.
```YAML
nextcloud_webroot: "/opt/nextcloud"
```
The Nextcloud root directory.
### Database configuration
```YAML
nextcloud_db_backup: true
```
Whenever the role should backup the instance's database on the same host.
```YAML
nextcloud_db_host: "127.0.0.1"
```
The database server's ip/hostname where Nextcloud's database is located.
```YAML
nextcloud_db_backend: "mysql"
```
Database type used by nextcloud.

Supported values are:
- mysql
- mariadb
- pgsql _(PostgreSQL)_

```YAML
nextcloud_db_name: "nextcloud"
```
The Nextcloud instance's database name.
```YAML
nextcloud_db_admin: "ncadmin"
```
The Nextcloud instance's database user's login
```YAML
nextcloud_db_pwd: false
```
**The Nextcloud instance's database user's password --required!!--**
### System configuration
```YAML
websrv_user: "www-data"
```
system user for the http server
```YAML
websrv_group: "www-data"
```
system group for the http server

## Dependencies

none

## Example Playbook
--TODO--
License
-------
BSD
