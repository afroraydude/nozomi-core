# Server Setup

Nozomi, as an extension of Slim, has a very similar setup for various Web Servers.

## NGINX

In your virtual host configuration:

```nginx
server {
    listen       80;
    server_name  localhost;
		root   /path/to/webroot;
		index  index.php;
		
        #charset koi8-r;

        #access_log  logs/host.access.log  main;
		
		location / {
			try_files $uri /index.php$is_args$args;
		}

		location ~ \.php {
			try_files $uri =404;
			fastcgi_split_path_info ^(.+\.php)(/.+)$;
			include fastcgi_params;
			fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
			fastcgi_param SCRIPT_NAME $fastcgi_script_name;
			fastcgi_index index.php;
			fastcgi_pass 127.0.0.1:9000;
    }
}
```

## Apache

In your `.htaccess` file:

```apacheconf
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

## HipHop

In your configuration file:

```virtualhost
Server {
    SourceRoot = /path/to/public/directory
}

ServerVariables {
    SCRIPT_NAME = /index.php
}

VirtualHost {
    * {
        Pattern = .*
        RewriteRules {
                * {
                        pattern = ^(.*)$
                        to = index.php/$1
                        qsa = true
                }
        }
    }
}
```

## IIS

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="slim" patternSyntax="Wildcard">
                    <match url="*" />
                    <conditions>
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="index.php" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
```

## lighttpd

In your configuration file (lighttpd >= 1.4.24):

```
url.rewrite-if-not-file = ("(.*)" => "/index.php/$0")
```