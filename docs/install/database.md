# Database Setup

Nozomi runs on PDO and connects to MySQL/MariaDB databases.

## Setup

Seetup is fairly simple, just create a MySQL user and database for Nozomi to run on.

```sql
CREATE USER 'nozomi_site'@'localhost' IDENTIFIED BY 'toor';
GRANT USAGE ON *.* TO 'nozomi_site'@'localhost';
GRANT SELECT, CREATE, DELETE, INDEX, INSERT, UPDATE, EXECUTE, SHOW VIEW  ON `nozomi_site`.* TO 'nozomi_site'@'localhost';
FLUSH PRIVILEGES;
```