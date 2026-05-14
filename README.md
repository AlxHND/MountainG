# MountainG

## Video Preview Path Rules

`video_preview` / `preview_video` is built in [class.galleries.php](/home/alexl/Projects/MountainG/classes/class.galleries.php:4924).

Current public/relative path logic:

1. Preview can be returned only if there is a record in `galleries_video_previews` for the gallery.
2. `preview_id` is taken from `galleries_video_previews.id`.
3. Preview width is resolved in this order:
   - explicit width override
   - `galleries_video_previews.preview_width`
   - `VIDEO_PREVIEWS_DEFAULT_WIDTH`
   - fallback `320`
4. Format is currently allowed only for:
   - `mp4`
   - `webm`
5. File name is built as:

```text
{preview_width}_{preview_id}.{format}
```

Example:

```text
320_512.mp4
```

6. Relative path is built as:

```text
/{first_digit_of_gallery_id}/{first_digit_of_preview_id}/{preview_width}_{preview_id}.{format}
```

Example for gallery `247340`, preview id `512`, width `320`, format `mp4`:

```text
/2/5/320_512.mp4
```

For API/RSS output the leading slash is removed, so the external value becomes:

```text
2/5/320_512.mp4
```

This is what should be used in:

- JSON RSS field `video_preview`
- JSON RSS field `preview_video`
- preview callback payloads
- external preview API responses

## Video Preview Storage Config

Defined in [config/config.php](/home/alexl/Projects/MountainG/config/config.php:26):

```php
define("VIDEO_PREVIEWS_FOLDER", "/home/web1/xrhost.com/htdocs/video_previews_mgx");
define("VIDEO_PREVIEWS_URL", HOSTING . "/video_previews_mgx");
define("VIDEO_PREVIEWS_DEFAULT_WIDTH", 320);
define("VIDEO_PREVIEWS_DEFAULT_HEIGHT", 180);
```

Meaning:

- `VIDEO_PREVIEWS_FOLDER`  
  Absolute filesystem base path where preview files are stored.

- `VIDEO_PREVIEWS_URL`  
  Internal helper base URL. This is used inside the app, but external clients should rely on the short relative path instead of this URL.

- `VIDEO_PREVIEWS_DEFAULT_WIDTH`  
  Default width used in the preview file name if no explicit width is stored.

- `VIDEO_PREVIEWS_DEFAULT_HEIGHT`  
  Default target height for preview generation.

## Full Path Example

If:

- `VIDEO_PREVIEWS_FOLDER=/home/web1/xrhost.com/htdocs/video_previews_mgx`
- gallery id = `247340`
- preview id = `512`
- width = `320`
- format = `mp4`

then file path becomes:

```text
/home/web1/xrhost.com/htdocs/video_previews_mgx/2/5/320_512.mp4
```

and external short path becomes:

```text
2/5/320_512.mp4
```

## Deleted Content RSS

The project now supports a separate deleted-content feed by content type.

Supported feed types:

- `gallery`
- `video`

The feed returns only deleted global IDs (`gal_id`), separated by content type.

Important:

- only unique `global_id` values are returned
- feed output is grouped by `gal_id`
- sorting is based on the latest deletion timestamp for each `global_id`

### Data Source

Deleted content is registered in `galleries_delete_rss`.

This registry is filled when a gallery is hard-deleted through `Galleries::deleteGallery()`:

- [class.galleries.php](/home/alexl/Projects/MountainG/classes/class.galleries.php:1035)
- [class.galleries.php](/home/alexl/Projects/MountainG/classes/class.galleries.php:1057)

Important:

- `deleteGallery()` adds the gallery to the delete RSS registry before marking it as `delete`
- `trashGallery()` does **not** add anything to the delete RSS registry

### Registry Fields

Relevant fields in `galleries_delete_rss`:

- `gal_id` — global gallery/video ID
- `site_id` — site for which the delete item is recorded
- `gal_local_id` — local site ID
- `gal_type` — content type used to split gallery/video delete feeds
- `gal_url` — legacy delete URL, kept for old RSS compatibility
- `added_on` — deletion timestamp

### Feed Endpoints

XML feed:

```text
rssfeeder.php?pwd=...&site=123&deleted_ids=gallery
rssfeeder.php?pwd=...&site=123&deleted_ids=video
```

Plain text feed, one ID per line:

```text
rssfeeder.php?pwd=...&site=123&deleted_ids=gallery&format=plain
rssfeeder.php?pwd=...&site=123&deleted_ids=video&format=plain
```

Concrete examples:

```text
/rssfeeder.php?pwd=SECRET&site=12&deleted_ids=gallery
/rssfeeder.php?pwd=SECRET&site=12&deleted_ids=video
/rssfeeder.php?pwd=SECRET&site=12&deleted_ids=gallery&format=plain
/rssfeeder.php?pwd=SECRET&site=12&deleted_ids=video&format=plain
```

Supported format aliases:

- `plain`
- `txt`
- `text`

### Sorting

By default, deleted IDs are returned from newest to oldest.

Optional sort parameter:

```text
&sort=desc
&sort=asc
```

Examples:

```text
rssfeeder.php?pwd=...&site=123&deleted_ids=video&format=plain&sort=desc
rssfeeder.php?pwd=...&site=123&deleted_ids=gallery&sort=asc
```

More URL combinations:

```text
/rssfeeder.php?pwd=SECRET&site=12&deleted_ids=gallery&sort=desc
/rssfeeder.php?pwd=SECRET&site=12&deleted_ids=gallery&sort=asc
/rssfeeder.php?pwd=SECRET&site=12&deleted_ids=video&format=plain&sort=desc
/rssfeeder.php?pwd=SECRET&site=12&deleted_ids=video&format=plain&sort=asc
```

### Output Format

Plain text output:

```text
247340
247339
247100
```

XML output:

```xml
<deleteditem>
  <id>247340</id>
</deleteditem>
```

No additional fields are returned in the new deleted-ID feeds:

- no URL
- no title
- no thumbs
- no tags
- no models

### Legacy Delete RSS

The old URL-based delete RSS is still available through the legacy `deleted=1` flow.

It reads from the same `galleries_delete_rss` table but returns `gal_url` instead of `gal_id`.

### Schema Update

To support split gallery/video delete feeds, `gal_type` was added to `galleries_delete_rss`.

Use:

```bash
php setup/update-delete-rss-content-type.php
```

The update script:

- adds `gal_type`
- adds an index on `gal_type`
- backfills `gal_type` from `galleries` when possible
- prints basic stats for `video / gallery / unknown`

Older orphaned delete records may remain with empty `gal_type` if the original gallery row is already gone.  
Such rows are intentionally excluded from the typed delete-ID feeds.

### Admin UI

Deleted content can be inspected in the admin panel:

```text
index.php?act=deleted_content
```

The page provides:

- filters by `site_id`
- filters by `global_id`
- filter by content type (`all / gallery / video`)
- sorting and limit selection
- direct test links to XML and plain delete feeds for the selected site
