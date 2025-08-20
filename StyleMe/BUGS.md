# Bug Tracker

This project contains a lightweight bug tracker backed by `bugs.json` and a few helper scripts.

Usage

- Run the scanner to discover inline TODO/FIXME/BUG comments and add them to `bugs.json`:

  php scripts/discover_bugs.php

- After making a commit, the post-commit hook (if installed) runs a script that parses the commit message for `Fixes #<id>` and marks the corresponding bug as closed.

- To install the git hooks locally (copies to `.git/hooks`):

  php scripts/install_hooks.php

Format

`bugs.json` stores an array of bug objects:

- id: integer
- title: short description
- file: file path
- line: line number
- snippet: code snippet or comment
- status: open|closed
- created_at: ISO timestamp
- closed_at: ISO timestamp or null
- closed_by_commit: commit SHA that closed the bug or null

Notes

- This is an intentionally small tool to help track developer-noted bugs.
- For large teams or production usage, consider a dedicated issue tracker (GitHub Issues, Jira, etc.).
