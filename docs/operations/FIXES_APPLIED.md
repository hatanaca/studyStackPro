# Correções aplicadas (histórico resumido)

Notas sobre problemas que já foram tratados no **StudyTrack Pro**. Para o estado atual do código, prefira os testes (`make test`) e a documentação técnica em [../technical/DOCUMENTACAO_TECNICA.md](../technical/DOCUMENTACAO_TECNICA.md).

---

## Stack (referência)

- **Backend:** Laravel 11, PHP 8.2+, PostgreSQL 16, Redis 7, Sanctum, Reverb, Horizon  
- **Frontend:** Vue 3, Vite 5, TypeScript  
- **Infra:** Docker Compose, OpenResty (Nginx), PHP-FPM, Node (dev)

---

## Itens já abordados no repositório (exemplos)

1. **Seeders / PostgreSQL** — Ajustes em seeders para respeitar `NOT NULL` e utilizadores de demonstração (ver `backend/database/seeders/`).
2. **Horizon** — `Laravel\Horizon\HorizonServiceProvider` registado em `backend/config/app.php` quando Horizon está instalado.
3. **Encoding UTF-8** — Ficheiros PHP do projeto devem estar em UTF-8 (acentos em mensagens e seeders).
4. **CORS, Sanctum, throttles** — Ver `backend/config/cors.php`, `backend/config/sanctum.php`, `AppServiceProvider`, `routes/api.php`.
5. **Métricas / filas** — `RecalculateMetricsJob` na fila `metrics`; supervisão em `config/horizon.php`.

---

## Onde acompanhar novas correções

- Commits e PRs no Git  
- [ERROS-CORRIGIDOS.md](ERROS-CORRIGIDOS.md) (se mantido)  
- Testes de feature em `backend/tests/Feature/`

*Versões antigas deste ficheiro continham blocos de código com formatação corrompida; o conteúdo foi condensado para evitar informação incorreta ou ilegível.*
