# Installation

Installation assumes you have already completed database setup. If you have not, refer to [this]

## Via Composer

Include `nozomi-core` repository into Composer:

```json
    "repositories": [{
        "type": "vcs",
        "url": "https://github.com/afroraydude/nozomi-core"
    }],
    "require": {
        "afroraydude/nozomi-core": "dev-master",
        ...
    }
```

Then run: 
```
composer update
```

# Usage
Adding Nozomi to your Slim application is very simple. In your public `index.php` file after you define your app, dependencies, and settings:
```php
$nozomi = new \Nozomi\Core\Nozomi($app);
```