Scripts for the lightweight bug tracker

- discover_bugs.php — scans the repo for TODO/FIXME/BUG comments and appends them to bugs.json
- update_bugs_from_commit.php — reads the latest commit message and closes referenced bugs using "Fixes #<id>"
- install_hooks.php — copies `.githooks/*` into `.git/hooks`

How it works

1. Run `php scripts/discover_bugs.php` to seed `bugs.json`.
2. Install hooks: `php scripts/install_hooks.php`.
3. When you commit and include `Fixes #<id>` in the commit message, the post-commit hook runs and marks bugs closed.
