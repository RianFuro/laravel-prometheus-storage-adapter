# laravel-prometheus-storage-adapter

This packag provides an `Adapter` implementation for [endclothing/prometheus_client_php](https://github.com/endclothing/prometheus_client_php),
using Laravel's Eloquent to store metrics in your database. Is it a good idea? Probably not, but here it is anyway - maybe it'll fit your needs.

## Installation

```
composer require  rianfuro/laravel-prometheus-storage-adapter
php artisan migrate
```
There's no configuration attached to this library, the adapter will simply use your default connection from your `database.php`.

## Usage

You can then simply register the adapter in your ServiceProvider, for example as follows:
```
        $this->app->bind(Adapter::class, LaravelPrometheusStorageAdapter\EloquentStorageAdapter::class);
        $this->app->singleton(CollectorRegistry::class);
```
