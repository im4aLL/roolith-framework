# CMS installer (release asset)

The optional CMS admin sources ship as `installer.zip`, which stays tracked in git for reference and local install (231K, owner decision Sep 2026). The zip is opaque and cannot be reviewed, so it is omitted from distribution artifacts via `composer create-project` exclusions. CMS mode is an explicit opt-in via the `APP_ENABLE_CMS` env flag.

## Enabling CMS

1. Install the CMS release asset for your version (see Release asset below).
2. Set in `.env`:

```
APP_ENABLE_CMS=1
```

3. Core mounts `app/Http/cms-routes.php` plus `app/Utils/Admin/functions.php` only when the flag is on and those files exist. Missing files are skipped so core boots without the asset. The legacy `cms-constant.php` file is still loaded when present for existing deploys.

## Release asset flow

Publish `installer.zip` as a GitHub Release asset with a checksum published alongside in the release notes; no checksum sidecar is tracked in git (owner decision Sep 2026):

```bash
# Maintainer: attach to a release (example v1.2.0)
gh release upload v1.2.0 installer.zip --clobber
shasum -a 256 installer.zip
# paste the printed hash into the release notes as the checksum

# Deployer: fetch plus verify plus install
gh release download v1.2.0 --pattern 'installer.zip' --dir /tmp
shasum -a 256 /tmp/installer.zip  # compare with the hash in the release notes
unzip -o /tmp/installer.zip -d /tmp/cms-asset
cp -R /tmp/cms-asset/installer/* /path/to/app/
```

Tracked binary (kept in git per owner decision Sep 2026, for reference and local install only - never ships in dist):

- Size: 231K, 660620 bytes unpacked, 128 files
- SHA256: `8f9dba0725e509a321002c6db0a41086f6f64717d83b3017a669938659bcd45b`
- Top paths: `installer/cms-constant.php`, `installer/cms-install.sql`, `installer/cms-with-sample-data.sql`, `installer/install.md`, `installer/app/Http/cms-routes.php`, `installer/app/Controllers/Admin/`, `installer/app/Models/Admin/`, `installer/app/Utils/Admin/`, `installer/source/js/admin/admin.js`, `installer/source/scss/admin/admin.scss`, `installer/views/admin/`

New releases MUST publish a fresh checksum; do not reuse the hash above.

## Why `/installer.zip` 404s

`.htaccess` denies `installer.zip` with `RedirectMatch 404` so existence is hidden:

```
curl -i http://localhost:8080/installer.zip  # -> 404
```

The file is also excluded from distribution:

- `.gitattributes`: `/installer.zip export-ignore` (GitHub archives omit it)
- `composer.json` `archive.exclude`: `installer.zip`, `.github`, `documentation`, `tests` (composer archives omit them)
- `.dockerignore`: `installer.zip` (lean build context only)
- `.htaccess`: `RedirectMatch 404` so the tracked binary stays hidden over HTTP

Note: `composer.json` `archive.exclude` omits `tests` entirely while `.gitattributes` keeps `/tests/.gitkeep` via `-export-ignore`. This difference is intentional: git needs `.gitkeep` to preserve the otherwise-empty `tests/` directory, while distribution archives omit tests entirely.

Verify local tracked state plus distribution absence:

```bash
git ls-files | grep -x installer.zip  # -> tracked locally
git archive HEAD | tar -t | grep -E "^installer\.zip$" || echo "no installer.zip in archive"  # -> absent from git archive
composer archive --format=zip --file=/tmp/roolith-test && unzip -l /tmp/roolith-test.zip | grep -E "installer\.zip" || echo "no installer.zip in composer archive"  # -> absent from composer archive
shasum -a 256 installer.zip  # -> checksum on demand, no sidecar file tracked
```
