server {
    listen       80;
    server_name  {HOSTNAME};

    root {PUBLICDIR};
    access_log {LOGDIR}/access.log main;

    location / {
        index index.php index.html index.htm;
    }

    location ~ \.php$ {
        fastcgi_pass  unix:{SOCKPATH};
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /public/$fastcgi_script_name;
        include       fastcgi_params;
    }
}

