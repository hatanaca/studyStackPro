/**
 * Servidor local de ingest para modo debug (NDJSON em debug-0a1699.log).
 * O frontend faz POST para http://127.0.0.1:7251/ingest/...
 *
 * Uso: na raiz do repo → npm run debug:ingest
 * Deixe este terminal aberto enquanto usa o app no browser.
 *
 * Se a porta 7251 já estiver ocupada pelo ingest do Cursor, não precisa deste script:
 * o Cursor exige o header X-Debug-Session-Id (o JSON body sozinho devolve "missing-session-id").
 *
 * Teste curl (Git Bash), com a sessão atual do modo debug:
 *   curl -X POST "http://127.0.0.1:7251/ingest/086e8d00-457e-4a30-82b0-abf450d19c28" \
 *     -H "Content-Type: application/json" \
 *     -H "X-Debug-Session-Id: 0a1699" \
 *     -d '{"sessionId":"0a1699","message":"teste"}'
 */
import http from 'node:http'
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const REPO_ROOT = path.join(__dirname, '..')
const LOG_FILE = path.join(REPO_ROOT, 'debug-0a1699.log')
const PORT = Number(process.env.DEBUG_INGEST_PORT || 7251)
const HOST = process.env.DEBUG_INGEST_HOST || '127.0.0.1'
const INGEST_PREFIX = '/ingest/'

function cors(res, status, extra = {}) {
  res.writeHead(status, {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'POST, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type, X-Debug-Session-Id',
    ...extra,
  })
}

const server = http.createServer((req, res) => {
  if (req.method === 'OPTIONS') {
    cors(res, 204)
    res.end()
    return
  }

  if (req.method !== 'POST' || !req.url?.startsWith(INGEST_PREFIX)) {
    cors(res, 404)
    res.end('Not found')
    return
  }

  const chunks = []
  req.on('data', (c) => chunks.push(c))
  req.on('end', () => {
    const raw = Buffer.concat(chunks).toString('utf8').trim()
    let line = raw
    if (raw) {
      try {
        line = JSON.stringify(JSON.parse(raw))
      } catch {
        line = JSON.stringify({ parseError: true, raw: raw.slice(0, 2000) })
      }
    } else {
      line = '{}'
    }
    try {
      fs.appendFileSync(LOG_FILE, `${line}\n`, 'utf8')
    } catch (e) {
      console.error('[debug-ingest] falha ao escrever log:', e.message)
    }
    cors(res, 204)
    res.end()
  })
})

server.on('error', (err) => {
  if (err.code === 'EADDRINUSE') {
    console.error(
      `[debug-ingest] Porta ${PORT} em uso. Feche o outro processo ou use:\n` +
        `  set DEBUG_INGEST_PORT=7252 && npm run debug:ingest\n` +
        `(e atualize a URL nos fetch do frontend para a mesma porta.)`,
    )
  } else {
    console.error('[debug-ingest]', err)
  }
  process.exit(1)
})

server.listen(PORT, HOST, () => {
  console.log(`[debug-ingest] escutando http://${HOST}:${PORT}${INGEST_PREFIX}*`)
  console.log(`[debug-ingest] escrevendo em ${LOG_FILE}`)
})
