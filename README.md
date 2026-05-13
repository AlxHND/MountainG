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
