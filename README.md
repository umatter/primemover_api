# Primemover
## Installation
- https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-ubuntu-18-04-de
- Use Virtual Host Template provided below
- Install Composer
- Install Git commands
- Clone API Repository into vhost-root folder (/var/www/primemover.example)
- Change to Repository dir 
- Rename `env.example` to `.env`
- Modify `.env` (db access)
- Run: `composer install` and resolve dependencies/issues 
- Run: `php artisan down` 
- Run: `php artisan migrate --seed`
- Run: `php artisan optimize:clear`
- Run: `php artisan key:generate`
- Run: `php artisan up`
- Make sure directories `storage` and `bootstrap` are writable
- Access API URL to test access `https://primemover.example/api/v1`

#### Virtual Host Template: 
    <VirtualHost *:80>
        ServerAdmin webmaster@primemover.example
        ServerName primemover.example
        ServerAlias www.primemover.example
        DocumentRoot /var/www/primemover.example/public
        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined
    
        <Directory "/var/www/primemover.example">
            Options Indexes FollowSymLinks
            AllowOverride All
            Require all granted
        </Directory>
    
        RewriteEngine on
        RewriteCond %{SERVER_NAME} =www.primemover.example [OR]
        RewriteCond %{SERVER_NAME} =primemover.example
        RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,NE,R=permanent]
    </VirtualHost>
    
## Infrastructure
### API: Hetzner, Helsinki Finnland
API server running PHP/MySQL based Laravel Web Application.
<table>
<thead>
<tr>
<td>Title</td>
<td>Description</td>
<td>Notes</td>
</tr>
</thead>
<tbody>
<tr>
<td>Type:</td>
<td>Virtual Private Server</td>
<td></td>
</tr>
<tr>
<td>SSH connection:</td>
<td>ssh root@135.181.37.96 -i ~/.ssh/runner-api</td>
<td></td>
</tr>
<tr>
<td>OS:</td>
<td>Ubuntu 18.04 LTS</td>
<td></td>
</tr>
<tr>
<td>IPv4:</td>
<td>135.181.37.96</td>
<td></td>
</tr>
<tr>
<td>URL:</td>
<td>https://primemover.example/api/v1</td>
<td></td>
</tr>
<tr>
<td>MySQL root:</td>
<td>#And20Qom5F</td>
<td></td>
</tr>
<tr>
<td>MySQL sphinx:</td>
<td>CHANGEME-backend-password</td>
<td></td>
</tr>
<tr>
<td>MySQL primemover:</td>
<td>CHANGEME-backend-password</td>
<td></td>
</tr>
<tr>
<td>Specs:</td>
<td>3vCore | 4GB RAM | 80 GB | 150 GB (scalable)</td>
<td></td>
</tr>
<tr>
<td>Network:</td>
<td>10 Gbps | 20 TB Traffic / Month</td>
<td></td>
</tr>
<tr>
<td>Backup:</td>
<td>Not configured (use based fee)</td>
<td></td>
</tr>
<tr>
<td>Snapshots:</td>
<td>Available (use based fee)</td>
<td></td>
</tr>
<tr>
<td>Expires:</td>
<td>Never, done manually</td>
<td></td>
</tr>
<tr>
<td>SSH Key:</td>
<td>runner-api</td>
<td></td>
</tr>

</tbody>
</table>


### Runner: Infomaniak, Vernier

<table>
<thead>
<tr>
<td>Title</td>
<td>Description</td>
<td>Notes</td>
</tr>
</thead>
<tbody>
<tr>
<td>SSH connection:</td>
<td>ssh ubuntu@195.15.226.18 -i ~/.ssh/runner-b</td>
<td></td>
</tr>
<tr>
<td>Type:</td>
<td>Virtual Private Server</td>
<td></td>
</tr>
<tr>
<td>OS:</td>
<td>Ubuntu 18.04 LTS</td>
<td></td>
</tr>
<tr>
<td>IPv4:</td>
<td>195.15.226.18</td>
<td></td>
</tr>
<tr>
<td>User:</td>
<td>ubuntu</td>
<td></td>
</tr>
<tr>
<td>Password:</td>
<td>CHANGEME-backend-password</td>
<td></td>
</tr>
<tr>
<td>Additional role:</td>
<td>VNC Server</td>
<td></td>
</tr>
<tr>
<td>VNC Password:</td>
<td>CHANGEME</td>
<td></td>
</tr>
<tr>
<td>VNC Viewer:</td>
<td>Staff12%</td>
<td></td>
</tr>
<tr>
<td>VNC Connection:</td>
<td>ssh ubuntu@195.15.226.18 -i ~/.ssh/runner-b -L 5901:127.0.0.1:5901 -C -N -l ubuntu 195.15.226.18
</td>
<td></td>
</tr>
<tr>
<td>Specs:</td>
<td>12vCore | 36GB RAM | 100 GB</td>
<td></td>
</tr>
<tr>
<td>Network:</td>
<td>10 Gbps</td>
<td></td>
</tr>
<tr>
<td>Backup:</td>
<td>Not configured (use based fee)</td>
<td></td>
</tr>
<tr>
<td>Snapshots:</td>
<td>Available (use based fee)</td>
<td></td>
</tr>
<tr>
<td>Expires:</td>
<td>04.03.21 (T-90)</td>
<td></td>
</tr>
<tr>
<td>SSH Key:</td>
<td>runner-b</td>
<td></td>
</tr>
</tbody>
</table>


### API: Metanet, Zürich
Current API runs on a shared hosting environment which is not configured to be accessed over SSH.
<table>
<thead>
<tr>
<td>Title</td>
<td>Description</td>
<td>Notes</td>
</tr>
</thead>
<tbody>
<tr>
<td>URL:</td>
<td>https://siaw.primemover.example/api/v1</td>
<td></td>
</tr>
<tr>
<td>Type:</td>
<td>Shared Hosting</td>
<td></td>
</tr>
<tr>
<td>IPv4:</td>
<td>80.74.128.40</td>
<td></td>
</tr>
</tbody>
</table>

### API User Authorization
1. Send a POST request with 2 parameters to https://primemover.example/api/v1/login
   ```
   {
        email: user@primemover.example,
        password: CHANGEME-backend-password
   }
   ```
2. Take `access_token` from response 
3. Use `headers` to send `Authorization` key with the value from `access_token` in each request in this format: `Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9......`
