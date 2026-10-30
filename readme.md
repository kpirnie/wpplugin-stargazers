# US Star Gazers

[![GitHub Issues](https://img.shields.io/github/issues/kpirnie/wpplugin-stargazers?style=for-the-badge&logo=github&color=006400&logoColor=white&labelColor=000)](https://github.com/kpirnie/wpplugin-stargazers/issues)
[![Last Commit](https://img.shields.io/github/last-commit/kpirnie/wpplugin-stargazers?style=for-the-badge&labelColor=000&logoColor=white&logo=data:image/svg%2Bxml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSIxLjgiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHJlY3QgeD0iMyIgeT0iNC41IiB3aWR0aD0iMTgiIGhlaWdodD0iMTYuNSIgcng9IjIiLz48bGluZSB4MT0iMyIgeTE9IjkuNSIgeDI9IjIxIiB5Mj0iOS41Ii8+PGxpbmUgeDE9IjgiIHkxPSIyLjUiIHgyPSI4IiB5Mj0iNi41Ii8+PGxpbmUgeDE9IjE2IiB5MT0iMi41IiB4Mj0iMTYiIHkyPSI2LjUiLz48L3N2Zz4=)](https://github.com/kpirnie/kptv-filter-app/commits/main)
[![License: MIT](https://img.shields.io/badge/License-MIT-orange.svg?style=for-the-badge&logo=opensourceinitiative&logoColor=white&labelColor=000)](LICENSE)
[![PHP](https://img.shields.io/badge/Min.-php8.4-777BB4?logo=php&logoColor=white&style=for-the-badge&labelColor=000)](https://php.net)
[![WordPress](https://img.shields.io/badge/Min.%20WP-6.8-3858e9?logo=wordpress&logoColor=white&style=for-the-badge&labelColor=000)](https://wordpress.org)
[![Kevin Pirnie](https://img.shields.io/badge/-KevinPirnie.com-000d2d?style=for-the-badge&labelColor=000&logoColor=white&logo=data:image/svg%2Bxml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSIxLjgiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+CiAgPGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iMTAiLz4KICA8ZWxsaXBzZSBjeD0iMTIiIGN5PSIxMiIgcng9IjQuNSIgcnk9IjEwIi8+CiAgPGxpbmUgeDE9IjIiIHkxPSIxMiIgeDI9IjIyIiB5Mj0iMTIiLz4KICA8bGluZSB4MT0iNC41IiB5MT0iNi41IiB4Mj0iMTkuNSIgeTI9IjYuNSIvPgogIDxsaW5lIHgxPSI0LjUiIHkxPSIxNy41IiB4Mj0iMTkuNSIgeTI9IjE3LjUiLz4KPC9zdmc+Cg==)](https://kevinpirnie.com/)

A WordPress plugin that pulls weather and space data into your site and shows it with Gutenberg blocks: NOAA/Open-Meteo forecasts and alerts, NASA's Astronomy Picture of the Day, near-Earth objects, DONKI and SWPC space weather alerts, sun and moon times, planet positions, a star chart, and a light pollution map.

## Requirements

- WordPress 6.8+
- PHP 8.4+
- WP-CLI (for syncing)

## Installation

1. Download the latest zip from [Releases](https://github.com/kpirnie/wpplugin-stargazers/releases).
2. Upload it through **Plugins → Add New → Upload Plugin**, or extract it to `/wp-content/plugins/`.
3. Activate **US Star Gazers**.

## Configuration

### SGU Options

| Tab | Setting | Notes |
| --- | --- | --- |
| API Settings | NOAA User Agent | Optional. NOAA needs no key, but asks for an identifying User-Agent. |
| API Settings | AstronomyAPI Keys | App ID + App Secret pairs from [AstronomyAPI](https://docs.astronomyapi.com/). Used for planet positions and moon phase. Up to 25 keys. |
| API Endpoints | AstronomyAPI Endpoint | The AstronomyAPI base endpoint. |

Open-Meteo and NOAA need no keys and are already configured.

### Post type settings

Each synced post type has its own settings for its endpoint and keys:

| Post type | Settings |
| --- | --- |
| CME Alerts | Endpoint, NASA API keys |
| Solar Flare Alerts | Endpoint, NASA API keys or reuse the CME keys |
| Geo Magnetic Alerts | Endpoint |
| Space Weather Alerts | Endpoint |
| NASA NEOs | Endpoint, NASA API keys or reuse the CME keys |
| NASA APOD | Archive page, endpoint, NASA API keys or reuse the CME keys |

NASA API keys are free at [api.nasa.gov](https://api.nasa.gov/).

APOD follows the endpoint you set: if it points at `api.nasa.gov`, the NASA APOD API is used; otherwise it reads the [NASA Science APOD feed](https://science.nasa.gov/feed/apod-basic/) and article pages. The archive page you pick sets the APOD archive URL, and single APODs live under `<archive>/apod/`.

## Blocks

Every block is in the inserter under the **sgup/** namespace.

### Weather

| Block | Description |
| --- | --- |
| Dashboard | Full weather dashboard |
| Current Weather | Current conditions |
| Today's Hourly Forecast | Hour-by-hour for today |
| Daily Forecast | Daily forecast |
| Weekly Forecast | 7-day forecast |
| Weekly Extended Forecast | Extended 7-day forecast |
| Alerts | Active NWS alerts for the location |
| Weather Map | Weather map |
| Location Picker | Lets visitors set their location by ZIP code or browser geolocation |

### Astronomy

| Block | Description |
| --- | --- |
| Astronomy Picture of the Day | Today's APOD, with images or YouTube/Vimeo embeds |
| Astronomy Menu | Navigation for the astronomy pages |
| Latest Astronomy Alerts | The newest alerts across all types |
| CME Alerts | Coronal mass ejections (NASA DONKI) |
| Solar Flare Alerts | Solar flares (NASA DONKI) |
| Geomagnetic Alerts | Geomagnetic storms |
| Space Weather Alerts | NOAA SWPC alerts |
| Near Earth Objects | NASA NeoWs close approaches |
| Sun Rise/Set Times | Sunrise, sunset, transit, and twilight (USNO) |
| Moon Rise/Set Times | Moonrise, moonset, and phase (USNO) |
| Planet Positions | Planet positions for the location (AstronomyAPI) |
| Star Chart | Interactive sky (VirtualSky) |
| Light Pollution Map | NASA VIIRS Black Marble night lights over OpenStreetMap |

## WP-CLI

Run the regular syncs on cron. The historical commands are one-time bulk imports.

### Regular sync

| Command | Description |
| --- | --- |
| `wp sgu sync data` | Syncs geomagnetic, solar flare, space weather, CME, NEO, and APOD data |
| `wp sgu sync imagery` | Downloads APOD imagery into the media library |
| `wp sgu sync both` | Runs data, then imagery |
| `wp sgu sync cleanup` | Removes duplicate posts and optimizes the database |
| `wp sgu help` | Lists all commands |

### Historical import

| Command | Description |
| --- | --- |
| `wp sgu historical apod [--start=<Y-m-d>] [--end=<Y-m-d>]` | Bulk imports APOD entries |
| `wp sgu historical imagery` | Downloads all missing APOD imagery |
| `wp sgu historical both [--start=<Y-m-d>] [--end=<Y-m-d>]` | Runs both of the above |

`--start` defaults to `2015-01-01` and `--end` defaults to today. API imports are rate limited and batched. If the API fails, or the APOD endpoint isn't the API, the import scrapes NASA Science pages instead. An APOD counts as a duplicate only when both its title and its date match.

### Example cron

```cron
0 * * * *  wp --path=/path/to/wordpress sgu sync both --quiet
30 3 * * * wp --path=/path/to/wordpress sgu sync cleanup --quiet
```

## Template overrides

Any template in the plugin's `templates/` directory can be overridden by copying it into your theme. Matching the relative path, the plugin looks in these folders, in order:

1. `templates/`
2. `sgu/`
3. `stargazers/`

For example, to override the single APOD template, copy `templates/astro/apod/single.php` to `your-theme/sgu/astro/apod/single.php`. The APOD archive also honors `archive-sgu_apod.php`.

## Data sources

- [NOAA / National Weather Service](https://www.weather.gov/documentation/services-web-api): forecasts and alerts
- [Open-Meteo](https://open-meteo.com/): current, hourly, and daily forecasts, and geocoding
- [Zippopotam.us](https://api.zippopotam.us/): ZIP code lookup
- [NASA APIs](https://api.nasa.gov/): APOD, NeoWs, DONKI
- [NASA Science APOD](https://science.nasa.gov/apod/): APOD feed and archive
- [NOAA SWPC](https://services.swpc.noaa.gov/): space weather
- [USNO Astronomical Applications](https://aa.usno.navy.mil/): sun and moon rise, set, and transit
- [AstronomyAPI](https://astronomyapi.com/): planet positions and moon phase
- [NASA GIBS](https://earthdata.nasa.gov/gibs): VIIRS Black Marble imagery
- [VirtualSky](https://virtualsky.lco.global/): star chart
- [Leaflet](https://leafletjs.com), [OpenStreetMap](https://www.openstreetmap.org/copyright), [CARTO](https://carto.com/attributions): maps
- [Meteocons](https://github.com/basmilius/weather-icons): weather icons

## Development

```bash
composer install
npm install
npm run start     # watch the blocks
npm run build     # compile the blocks, then build into distribute/
```

Work only in `source/`. The build:

- copies the PHP, templates, readme, and license into `distribute/`
- minifies the assets and the compiled blocks bundle with esbuild
- builds a production Composer autoloader
- generates `languages/sgup.pot`

The build stops if the plugin header `Version` doesn't match the `Stable tag` in `source/readme.txt`.

Never edit `distribute/` by hand. It's wiped and rebuilt on every build, and it's committed because the release zip is made from it.

### Releasing

Bump `Version` in `source/sgup.php` and `Stable tag` in `source/readme.txt`, run `npm run build`, commit, then push a `v*` tag. GitHub Actions zips `distribute/` as `sgup-<version>.zip` and attaches it to a GitHub release.

## License

MIT. See [LICENSE](LICENSE).