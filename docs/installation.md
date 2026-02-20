# Installation & Setup

## Requirements

- PHP 7.1 or above
- R/W access to `wp-content/uploads` on the WordPress site
- Administrator account on the WordPress site

## Optional

- WP-CLI installed on the WordPress host, if you wish to run the export commands from the command line

## Installation

1. Download or clone this plugin to your WordPress `wp-content/plugins` directory:
   ```
   wp-content/plugins/wp2grav_exporter/
   ```

2. Activate the plugin:
   ```bash
   wp plugin activate wp2grav_exporter
   ```
   Or activate `WP2Grav Exporter` via the WordPress admin under **Plugins**.

## Admin UI

Once activated, an export interface is available under **Tools > WP2Grav Exporter** in the WordPress admin. Each exporter has its own button, and an **Export All** button runs all exporters at once. Previously generated exports are listed with the option to delete them.

All export operations run via AJAX and require the `manage_options` capability (i.e., administrator role).

## Export Output Location

All exports are written to:
```
WP_ROOT/wp-content/uploads/wp2grav-exports/<username>-<YYYYMMDD>/
```

The export directory contains subdirectories populated by each exporter:

```
<export-dir>/
├── accounts/         # User YAML files (wp2grav-users)
├── config/
│   ├── groups.yaml   # Role/group definitions (wp2grav-roles)
│   └── site.yaml     # Site metadata (wp2grav-site)
├── data/wp-content/  # Media library files (wp2grav-posts)
├── pages/            # Page and post markdown files (wp2grav-posts)
└── plugins/
    └── wordpress-exporter-helper/  # Generated Grav plugin (wp2grav-post-types)
```

Re-running an export on the same day will overwrite any existing files in that export directory.

## Importing to Grav

After running the exporters, copy these directories to your Grav installation's `user/` directory:

| Export directory                     | Grav destination                          |
| ------------------------------------ | ----------------------------------------- |
| `accounts/`                          | `user/accounts/`                          |
| `config/`                            | `user/config/` (merge, don't overwrite)   |
| `data/`                              | `user/data/`                              |
| `pages/`                             | `user/pages/`                             |
| `plugins/wordpress-exporter-helper/` | `user/plugins/wordpress-exporter-helper/` |

## Notes

- For Grav 1.6 sites, the [admin-addon-user-manager](https://github.com/david-szabo97/grav-plugin-admin-addon-user-manager) plugin is recommended for managing exported users. Grav 1.7+ has user management built-in.
- This plugin exports all public post types. The `attachment` post type is intentionally excluded.
