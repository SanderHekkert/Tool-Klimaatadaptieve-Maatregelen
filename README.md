# Scriptie Julia — Klimaatadaptieve maatregelen (Bijlage E)

Webapplicatie om klimaatadaptieve maatregelen te verkennen, te filteren op basis van projectparameters en een selectie te exporteren als PDF. Gebaseerd op **Bijlage E**; uitgebreide toelichting verwijst naar de **Basisgids Klimaatadaptatie** (Van Wijnen).

## Functies

- Startpagina met keuze: alle maatregelen of een eigen selectie
- Tool met live filtering (schaalniveau, risico’s, oppervlakten, waterbergingsnorm in mm)
- Resultaten: maatregelen die voldoen of wegvallen, met redenen
- Planner: kosten en waterberging per ingevulde m²/stuks
- PDF-export van alle passende maatregelen (optioneel met kosten/water bij ingevulde hoeveelheid)
- Link naar de Basisgids-PDF

## Vereisten

- PHP 8.3+
- Composer
- Node.js 20+ (alleen voor Vite-assets, optioneel lokaal)

## Lokaal draaien

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build   # optioneel
php artisan serve
```

Open `http://127.0.0.1:8000`.

### Routes

| URL | Beschrijving |
|-----|----------------|
| `/` | Home / catalogus |
| `/maatregelen/start` | Maatregelen kiezen |
| `/maatregelen/tool` | Filtertool |
| `/basisgids-klimaatadaptatie.pdf` | Basisgids (PDF) |

Maatregeldata staat in `config/maatregelen.php`. Filterlogica in `app/Services/MaatregelFilterService.php`.

### Basisgids PDF

De Basisgids staat niet in de repository. Plaats het bestand lokaal bijvoorbeeld als:

- `public/documents/basisgids-klimaatadaptatie.pdf`, of
- `storage/app/documents/basisgids-klimaatadaptatie.pdf`

Op een server met object storage (S3-compatibel) kun je in `.env` instellen:

```env
BASISGIDS_DISK=s3
BASISGIDS_STORAGE_PATH=documents/basisgids-klimaatadaptatie.pdf
```

Uploaden kan met `php artisan basisgids:upload` (als de disk is geconfigureerd). Controleer of het bestand bereikbaar is met `php artisan basisgids:status`.

Alternatief: een vaste publieke URL:

```env
BASISGIDS_PDF_URL=https://...
```

Zie `config/basisgids.php`.

## Ontwikkeling

```bash
composer setup          # install + key + migrate + npm build
composer dev              # serve, queue, logs, vite
./vendor/bin/pint         # PHP code style
npm run build             # frontend assets
php artisan test
```

## Projectstructuur (kern)

```
app/Http/Controllers/MaatregelenToolController.php
app/Services/MaatregelFilterService.php
app/Support/BasisgidsStorage.php
config/maatregelen.php
resources/views/maatregelen-tool/
public/css/maatregelen-tool.css
public/js/maatregelen-tool.js
```

## Licentie

MIT
