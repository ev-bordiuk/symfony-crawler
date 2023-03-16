# symfony-crawler
### Description
Simple crawler which calculates domain hosted images on the page(s) and time spent for every particular page visit and html handle, based on [Symfony 6.2.](https://symfony.com/) and [Symfony UX Turbo](https://symfony.com/bundles/ux-turbo/current/index.html).

### Requirements
PHP >=8.1, node 14, yarn 1.22.11(optionally), MySQL, [Symfony CLI](https://symfony.com/download).

### Installation
```
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```
Run `yarn install` or `npm install`.

### Usage
To start crawling run `php bin/console crawl {targetURL}`.

Then start server `symfony server:start`, run `npm run watch` or `npm run dev`.

See results on `localhost:8000/results`.

### Command options
By default command visits every domain related pages.

+ `--pages` or `-p` - specify maximum pages to scan.
+ `--deep` or `-d` - specify how deep walk through internal pages.
+ `--timeout` or `-t` - limit request timeout in seconds.
