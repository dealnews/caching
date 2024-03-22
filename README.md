# PHP Caching Library Wrapper

This library provides a standard caching interface and provides classes for
Memcached and Redis which implement the interface. We created this because
we were moving between different caching services and wanted a unified
interface.

## Todo

* Add PSR-6 and/or PSR-16 compatible classes

## Example

```ini
; config.ini in your app base directory

[caching.memcache]
caching.memcache.app.servers = 10.0.0.1:11211,10.0.0.2:11211

[caching.redis]
caching.redis.sessions.servers = 10.0.0.3,10.0.0.4
; supports redis auth and other options that the
; predis/predis library supports such as sentinel replication
caching.redis.sessions.username = someuser
caching.redis.sessions.password = XXXXXXXX
```

```php
<?php

$cache = \DealNews\Caching\Cache::factory("app");
$value = $cache->get("somekey");
$cache->set("somekey", 1);
$cache->delete("somekey");
```
