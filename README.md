# nozomi-core


## What is `nozomi-core`?
`nozomi-core` is the core libraries for Nozomi. It was made for ease and organizational purposes related to the Nozomi project.
It is built on top the original Slim Framework.

## Installation

### Via Composer
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
```sh
composer update
```

### Via `nozomi`

Clone the `nozomi` repository:
```sh
git clone https://github.com/afroraydude/nozomi.git
```

Enter the directory and install requirements:
```sh
cd nozomi

composer install
```
