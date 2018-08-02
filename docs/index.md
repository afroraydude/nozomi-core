# Quick Start

## Getting Nozomi
Clone the `nozomi` repository:

```
git clone https://github.com/afroraydude/nozomi.git
```

## Installing dependencies
Enter the directory and install requirements:

```
cd nozomi

composer install
```

## Starting Nozomi

If you want to run Nozomi, it is best that you do so with a [Web Server](install/server.md). Otherwise, loading assets will not work if they are outside the public directory. If you still find it necessary to load through PHP's built in server, use this command:

```bash
php -S localhost:8080 -t public public/index.php
```