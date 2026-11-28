# Keeping the Test Suite Green

- Before every commit, run the whole suite with `php artisan test --compact` (no path), the same suites CI runs with `./vendor/bin/phpunit`. Commit only when it ends with zero failures and zero errors.
- Never commit with failing tests. Fix the code or the test, and state which one changed and why.
- Never delete or empty a test directory listed in `phpunit.xml`. If the last test in a directory is removed, keep a `.gitkeep` file there so the directory still exists.
- A skipped test must be explained by configuration (e.g. `skipUnlessFortifyHas()` while Fortify registration is disabled). Never add a skip to hide a failure.
- When behaviour changes, update the existing tests in the same commit. Do not delete tests without approval.
