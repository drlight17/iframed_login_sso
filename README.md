# Roundcube + Nextcloud with universal SSO instruction (not only login + password based NC authorization but also passwordless like SSO).
In order to use this you will need:
- configure [dovecot master password](https://doc.dovecot.org/configuration_manual/authentication/master_users/) on your mail server
- [dovecot_impersonate plugin](https://github.com/corbosman/dovecot_impersonate) in your Roundcube instance
- roundcube must be [allowed to be embedded](https://developer.mozilla.org/en-US/docs/Web/Security/Same-origin_policy) into iframe from your nextcloud
- [php-jwt](https://github.com/firebase/php-jwt) in your roundcube instance
- nc-login.php from this repo
- [nextcloud external sites plugin](https://apps.nextcloud.com/apps/external) installed and properly configured

## Step by step guide:
1. Configure master login, password and seperator in your dovecot config
2. Install and configure dovecot_impersonate plugin with the same separator from dovecot config
3. (!) Configure your roundcube web server or reverse proxy to allow embedding roundcube into nextcloud iframe (origin policies)
4. Place nc-login.php to the root dir of your roundcube instance
5. Place php-jwt in the root dir of your roundcube instance (you also could use composer for this but it will require some appropriate nc-login.php source code changes):
```
# cd /rouncude_path
# git clone https://github.com/firebase/php-jwt
```
6. Install external nextcloud app and add this url as external site:
`https://yourmailserver.address/nc-login.php?jwt={jwt}`

---
This could be used as a source for the new Roundcube plugin. I just have no time to build one for now.


