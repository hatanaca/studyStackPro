# Referência histórica (`StudyTrack_*.txt`)

Estes ficheiros são notas e planos antigos do projeto. **Podem conter detalhes desatualizados** (rotas, limites, nomes de classes).

Para decisões e números alinhados ao código atual, use:

- [`../technical/DOCUMENTACAO_TECNICA.md`](../technical/DOCUMENTACAO_TECNICA.md)
- [`../../backend/README.md`](../../backend/README.md)
- [`../../backend/routes/api.php`](../../backend/routes/api.php)
- [`../../backend/app/Providers/AppServiceProvider.php`](../../backend/app/Providers/AppServiceProvider.php)

## Inconsistências comuns (substituições conceituais)

Ao ler os `.txt`, interprete termos antigos da seguinte forma (alinhado ao repositório atual):

| Texto legado nos `.txt` | Estado real no repo |
|-------------------------|---------------------|
| **Chart.js** / imports `chart.js` | **ApexCharts** + **vue3-apexcharts** (`frontend/src/components/charts/`) |
| **Socket.io** / **Socket.io-client** | **Laravel Reverb** + **Laravel Echo** + **pusher-js** (real-time) |
| **RouteServiceProvider** para rate limit | **`AppServiceProvider`** (`RateLimiter::for(...)`) + atribuição de `throttle:*` em **`routes/api.php`** |
| **`Route::apiResource('study-sessions', …)`** | Rotas **explícitas** em `api.php`: CRUD, `POST …/start`, `PATCH …/{id}/end`, `GET …/active` (não é resource “puro”) |
| **Nginx** como único proxy | Imagem **OpenResty** em Docker (`docker/nginx/`); ver [`../../docker/README.md`](../../docker/README.md) |
| Endpoints sem prefixo `/api/v1` | API versionada: prefixo **`/api/v1`** (ex.: `POST /api/v1/auth/login`); health em `/api/health`, `/health`, `/up` |
| **Laravel Telescope** como dado adquirido | **Não** está no `composer.json`; debug via **logs**, **`LogApiRequests`**, **Horizon**, **`queue:failed`**; Telescope só se instalado opcionalmente em dev |

Algumas inconsistências pontuais nos originais: limites de login antigos (5/min), paths relativos em tabelas sem repetir `/api/v1`.
