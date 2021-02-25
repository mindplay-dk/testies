### Upgrading From 0.x to 1.0

Version 0.x had functions in the global namespace - these have been moved to the `mindplay\testies` namespace.

In your test-suites, you must explicitly import the functions you need:

```php
use function mindplay\testies\{configure, test, ok, eq, run};
```
