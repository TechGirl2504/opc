import fs from 'node:fs'
import path from 'node:path'
import { execFileSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
const root = path.resolve(__dirname, '..')
const sourceSvg = path.join(root, 'public', 'cnmis-logo.svg')
const out192 = path.join(root, 'public', 'pwa-192x192.png')
const out512 = path.join(root, 'public', 'pwa-512x512.png')
const outApple = path.join(root, 'public', 'apple-touch-icon.png')
const tmpDir = path.join(root, 'tmp', 'pwa-icons')

function run(cmd, args) {
  execFileSync(cmd, args, { stdio: 'inherit' })
}

if (!fs.existsSync(sourceSvg)) {
  throw new Error(`Missing logo source: ${sourceSvg}`)
}

fs.mkdirSync(tmpDir, { recursive: true })

// macOS Quick Look renders the SVG into a PNG thumbnail that we can reuse for PWA assets.
// This keeps the installed app icon in sync with the CNMIS brand mark without extra deps.
run('qlmanage', ['-t', '-s', '512', '-o', tmpDir, sourceSvg])

const rendered = path.join(tmpDir, 'cnmis-logo.svg.png')
if (!fs.existsSync(rendered)) {
  throw new Error(`Expected rendered PNG not found: ${rendered}`)
}

fs.copyFileSync(rendered, out512)
run('sips', ['-z', '192', '192', out512, '--out', out192])
run('sips', ['-z', '180', '180', out512, '--out', outApple])

console.log(`Generated PWA icons from ${path.relative(root, sourceSvg)}`)
