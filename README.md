# Whispar Launch Audit

A small, **read-only WordPress admin plugin** that presents a practical pre-launch checklist under **Tools → Launch Audit**.

This is a standalone demonstration project by [Whispar Design](https://whispardesign.com/), focused on WordPress implementation and launch QA. It does not depend on a commercial theme, page builder, or client code.

## What it checks

| Check | Why it matters |
| --- | --- |
| Home URL and WordPress URL | Review HTTPS configuration before a public launch |
| Search engine visibility | Spot the WordPress setting that discourages indexing; expected on some staging sites |
| Permalink structure | Identify plain permalinks that may warrant review |
| Site title | Flag an empty site title |
| Default tagline | Identify the initial “Just another WordPress site” placeholder |
| Debug mode | Flag enabled WP_DEBUG for review before production |
| Plugin updates | Report **cached** outstanding plugin updates, without forcing network requests |

Results are **Pass** or **Review**, not a claim of certification. The plugin never changes settings, updates plugins, or sends data to external services.

## Requirements

- WordPress 6.0 or later
- PHP 7.4 or later
- Administrator permissions to view the report

## Install on a test/staging site

1. Download this repository or copy the PHP plugin file into a directory named whispar-launch-audit under wp-content/plugins/.
2. Activate **Whispar Launch Audit** in **Plugins**.
3. Visit **Tools → Launch Audit** in WordPress admin.
4. Review each finding based on whether the environment is staging or production.
5. Deactivate and remove it when finished, if desired.

**Do not test new plugins on a live client website without appropriate backups and approval.**

## Technical notes

- Uses standard WordPress APIs: add_management_page, get_option, get_site_transient, and URL helpers.
- Restricts the admin report to users with manage_options.
- Escapes output and performs no writes or network calls.
- Uses the existing update transient; the plugin does not verify that cached update information is fresh.
- Does not inspect theme or plugin vulnerability databases, remote redirects, broken links, or actual search-index status.

## Development checks

Run from the repository root:

    php -l whispar-launch-audit.php
    php tests/smoke.php

The included smoke test covers a nominal public site and a staging-like configuration with pending updates. GitHub Actions runs these checks on pushes and pull requests.

## Status

**Version 0.1.0 — example implementation.** PHP syntax and local smoke tests pass. It has **not yet been validated inside a full WordPress installation**; test on staging before relying on it.

## License

GPL-2.0-or-later. See [GNU General Public License v2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html).
