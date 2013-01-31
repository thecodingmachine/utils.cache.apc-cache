Mouf APC cache service
======================

This package contains an implementation of Mouf's CacheInterface for the APC cache system.
To learn more about the cache interface, please see the [cache system documentation](http://mouf-php.com/packages/mouf/utils.cache.cache-interface).

Compared to Mouf's other cache implementations, the APC cache system comes with an additional feature: _a fallback meachanism_.

In practice, if you do not have the APC extension installed on your computer, the APCCache class
can use a fallback class. By default, the FileCache system is used (it stored cache elements in files).