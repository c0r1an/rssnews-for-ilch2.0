# RSSNEWS for Ilch 2.0

RSSNEWS is an Ilch 2.0 module for aggregating RSS/Atom feeds, sanitizing imported content, and optionally mirroring imported entries into the Ilch `article` module.

## Features

- RSS and Atom feed import
- Per-feed update interval
- Auto-fetch only when interval has elapsed
- Cron endpoint: `rssnews/cron/fetchAll`
- Fallback auto-fetch hook via `AfterDatabaseLoad`
- Stable duplicate detection via `GUID`, `link`, and `title+date`
- Feed categories and tags
- Optional mirroring into Ilch `article` module
- Frontend layout selection (list, 2-column grid, 3-column grid)
- Image and video extraction for richer imported content

## Installation

1. Copy the `rssnews` folder into:
   `application/modules/rssnews`
2. Open the Ilch admin area.
3. Install the module in the module administration.
4. Open `RSS News` in the admin area and configure:
   - frontend layout
   - default posting mode
   - article category
   - read access groups
   - cron token
5. Add feeds under:
   `Admin -> RSS News -> Feeds`

## Cron

Use the cron endpoint:

`/index.php/rssnews/cron/fetchAll/token/{TOKEN}`

Optional:

`/index.php/rssnews/cron/fetchAll/token/{TOKEN}/force/1`

The token is configured in:

`Admin -> RSS News -> Settings`

## Posting Modes

- `aggregator`: store only in RSSNEWS
- `article`: mirror into the Ilch `article` module
- `both`: store in RSSNEWS and mirror into `article`
- `global`: a feed inherits the module-wide default posting mode

## Notes

- Existing mirrored items are updated on re-fetch.
- If a feed image is too small, RSSNEWS may try to use `og:image` or similar metadata from the source article page.
- Video embeds are supported for common sources such as YouTube, Vimeo, and direct video files.

## Suggested GitHub Repository Name

GitHub repository names should not contain spaces.

Recommended:

- `rssnews-for-ilch2.0`
- `RSSNEWS-for-Ilch2.0`

## Package Export

The module folder is prepared so it can be published as a standalone package repository.

Recommended repository root:

- this `rssnews` folder itself

Included package files:

- `README.md`
- `LICENSE.md`
- `CHANGELOG.md`
- `composer.json`
- `export-module.ps1`

### Build a ZIP package

Run inside the module folder:

```powershell
.\export-module.ps1
```

This creates:

- `dist/rssnews-v{version}.zip`

The ZIP contains a top-level `rssnews` folder, so it can be unpacked directly into:

- `application/modules/`

Important:

- Use the generated release ZIP if you want the top-level folder to be exactly `rssnews`
- GitHub's automatic "Source code (zip)" download uses the repository name as folder name and cannot be forced to use `rssnews`

## Public Release Checklist

1. Put this `rssnews` folder into its own Git repository root.
2. Create a public GitHub repository, recommended name:
   `rssnews-for-ilch2.0`
3. Commit the module contents.
4. Create a first release tag:
   `v1.0.0`
5. Optionally run:

```powershell
.\export-module.ps1
```

to attach a ready-to-install ZIP to the GitHub release.
