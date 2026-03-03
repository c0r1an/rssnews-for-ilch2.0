# Release Checklist

## Before Publishing

- Verify module installs cleanly in Ilch 2.0
- Verify feed import works with at least:
  - one RSS feed
  - one Atom feed
  - one feed with image content
  - one feed with video content
- Verify `article` mirroring works
- Verify cron endpoint works with the configured token
- Verify admin settings and feed forms save correctly

## GitHub Release

- Repository name:
  - `rssnews-for-ilch2.0`
- Visibility:
  - public
- First tag:
  - `v1.0.0`
- Optional ZIP artifact:
  - run `.\export-module.ps1`
  - upload the generated ZIP from `dist/`
  - this ZIP should be shared with users because it contains the correct top-level folder `rssnews`

## After Publishing

- Add the real GitHub URL to the README if desired
- Announce installation path:
  - `application/modules/rssnews`
