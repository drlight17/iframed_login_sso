# Roundcube Webmail iframe SSO connector plugin

To successfully use this plugin you will need:
- configure [dovecot master password](https://doc.dovecot.org/configuration_manual/authentication/master_users/) on your mail server
- [dovecot_impersonate plugin](https://github.com/corbosman/dovecot_impersonate) in your Roundcube instance
- roundcube must be [allowed to be embedded](https://developer.mozilla.org/en-US/docs/Web/Security/Same-origin_policy) into iframe on your server
- (OPTIONAL)[nextcloud external sites plugin](https://apps.nextcloud.com/apps/external) installed and properly configured

## Step by step guide:
1. Configure master login, password and seperator in your dovecot config
2. Install and configure dovecot_impersonate plugin with the same separator from dovecot config
3. (OPTIONAL)Configure your roundcube web server or reverse proxy to allow embedding roundcube into your server iframe (origin policies)
4. Place plugin to the plugins dir of your roundcube instance
```
# cd /rouncude_path/plugins
# git clone https://github.com/drlight17/iframed_login_sso 
```
5. Edit plugin config file to your needs:
```
# cp /rouncude_path/plugins/iframed_login_sso/config.inc.php.dist /rouncude_path/plugins/iframed_login_sso/config.inc.php
# vi /rouncude_path/plugins/iframed_login_sso/config.inc.php
```
6. (OPTIONAL) To get the key from  nextcloud external app use command below:
```
sudo -u www-user /path/to/your/nextcloud/occ config:app:get external jwt_token_pubkey_es256
```
7. Place "iframed_login_sso" in your roundcube config file in plugins array variable
8. Use this url for iframe (!replace jwt_req_parameter with your set parameter in the plugin config.inc.php)
`https://yourroundcubeserver.address/?_autologin=yes&jwt_req_parameter=formed_jwt_token`
(OPTIONAL) For nextcloud external app use url below (with {jwt} placeholder, you can read more about it in the nextcloud external sites app settings menu)
`https://yourroundcubeserver.address/?_autologin=yes&jwt_req_parameter={jwt}`

---
[php-jwt](https://github.com/firebase/php-jwt) is used for jwt tokens decode functionality. 
All rights belong to their owners.


