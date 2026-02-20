# Export Posts (`wp2grav-posts`)

Exports all WordPress posts and pages as Grav-compatible Markdown files with YAML frontmatter.

![New Posts in Grav](images/sample-page-admin.png)

![New Posts in Grav](images/sample-page-render.png)


## Usage

**WP-CLI:**
```bash
wp wp2grav-posts
```

Or, to export a single post by ID:
```bash
wp wp2grav-posts --id=<POST_ID>
```

**Admin UI:** 

> [!NOTE]
> The admin page only supports exporting all posts at this time.  Individual post exports are not supported.

Click the **Export Posts** button under Tools > WP2Grav Exporter.

## What It Exports

- All public post types:
  - Published posts/ pages
  - Drafts
  - Scheduled posts/ pages
  - Trashed posts/ pages
- Post revisions (saved alongside the main post file)
- Post comments (saved alongside the main post file)
- Featured images
- Attached and inline media 
- ACF (Advanced Custom Fields) field data, if the ACF plugin is active

## Output Structure

```
<export-dir>/pages/
├── blog/
│   ├── blog.md               # Auto-generated Grav blog listing page
│   └── <post-slug>/
│       ├── wp_post.md        # Post content
│       ├── wp_post.md.<timestamp>.rev  # Revision files
│       ├── comments.yaml     # Comments (if any)
│       └── <featured-image>  # Featured image copy
├── products/
│   └── <product-slug>/
│       └── wp_product.md
└── <page-slug>/
    └── wp_page.md

<export-dir>/data/wp-content/uploads/
    └── <year>/<month>/       # All media files
```

### File Naming

- Main post file: `wp_<post_type>.md` (e.g., `wp_post.md`, `wp_page.md`)
- Revision files: `wp_<post_type>.md.<YYYYMMDD-HHmmss>.rev`
- Comments file: `comments.yaml`

### Directory Placement

| WordPress post type | Grav directory                      |
| ------------------- | ----------------------------------- |
| `post`              | `pages/blog/<slug>/`                |
| `product`           | `pages/products/<slug>/`            |
| `page` (and others) | `pages/<slug>/`                     |
| Trashed posts       | `pages/z_trashed/<slug>/`           |
| Hierarchical pages  | `pages/<parent-slug>/<child-slug>/` |

### Frontmatter Fields

Each `.md` file includes a YAML frontmatter block with:

- `title` - Post title
- `published` - Whether the post is published
- `publish_date` - Publication date
- `modified` - Last modified date
- `comments` - Whether comments are open
- `taxonomy.category[]` - Post categories
- `taxonomy.tag[]` - Post tags
- `media_order` / `hero_image` - Featured image filename
- `wp.post` - WordPress post metadata (ID, GUID, author, excerpt)
- `wp.meta` - All WordPress post meta fields
- `wp.meta.acf` - ACF field data (if ACF plugin is active)

### Comments File (`comments.yaml`)

Each comment includes: `id`, `parent_id`, `author`, `email`, `text` (converted to Markdown), `date`, `status` (`published`, `pending`, `spam`, or `deleted`), `user_agent`, `user_id`, `user_email`.

## Importing to Grav

Copy the content in `<export-dir>/pages/` directory to your Grav's `user/pages` directory.  You may wish to remove any existing pages before importing.
