Run the regular syncs on cron. The historical commands are one-time bulk imports.

## Data sources

- [NOAA / National Weather Service](https://www.weather.gov/documentation/services-web-api) — forecasts and alerts
- [NOAA SWPC](https://services.swpc.noaa.gov/) — space weather
- [NASA APIs](https://api.nasa.gov/) — APOD, NEO, DONKI
- [Open-Meteo](https://open-meteo.com/) — forecast fallback and geocoding
- [USNO](https://aa.usno.navy.mil/) — rise, set, and transit times

## Development

```bash
composer install
npm install
npm run start     # watch the blocks
npm run build     # compile blocks, then build into distribute/
```

Work in `source/`. Never edit `distribute/` — it is wiped and rebuilt on every build, and it is committed because the release zip and the GitHub updater are both made from it.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
