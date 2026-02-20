# Export All (`wp2grav-all`)

Runs all available exporters in sequence. This is the quickest way to export everything from WordPress in a single step.

![WP2Grav Exporter Admin Page](images/admin-page.png)

## Usage

**WP-CLI:**
```bash
wp wp2grav-all
```

**Admin UI:** 

Click the **Export All** button under Tools > WP2Grav Exporter.

## What It Does

Discovers and runs every `wp2grav-*.php` plugin file found in the `plugins/` directory (excluding itself). The exporters run in filesystem order, which typically means:

1. `wp2grav-post-types`
2. `wp2grav-posts`
3. `wp2grav-roles`
4. `wp2grav-site`
5. `wp2grav-users`

After completion, the CLI output shows which exporters ran and the path to the export directory.  If run from the Admin page, the Results window will display a success message along with a directory path to the exported files.

## Output

All export subdirectories are created under a single timestamped export directory:

```
wp-content/uploads/wp2grav-exports/<username>-<YYYYMMDD>/
```

See each individual exporter's documentation for the files it produces.

## After Exporting

Copy the exported files from `wp-content/uploads/wp2grav-exports/<export-dir>/` to your Grav `user` directory.
