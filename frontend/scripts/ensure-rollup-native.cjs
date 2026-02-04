/* eslint-disable no-console */
const { spawnSync } = require('node:child_process')

function isMusl() {
  // Similar approach to Rollup's own detection.
  try {
    if (process.platform !== 'linux') return false
    const header = process.report?.getReport?.().header
    return header ? !header.glibcVersionRuntime : false
  } catch {
    return false
  }
}

function main() {
  // Only needed on Linux where Rollup loads a native binding package.
  if (process.platform !== 'linux') return

  let rollupVersion
  try {
    // eslint-disable-next-line import/no-extraneous-dependencies
    rollupVersion = require('rollup/package.json').version
  } catch (e) {
    console.warn('[ensure-rollup-native] rollup not installed yet, skipping')
    return
  }

  const arch = process.arch
  const musl = isMusl()

  /** @type {Record<string, { gnu: string; musl: string }>} */
  const byArch = {
    x64: { gnu: 'linux-x64-gnu', musl: 'linux-x64-musl' },
    arm64: { gnu: 'linux-arm64-gnu', musl: 'linux-arm64-musl' },
  }

  const mapping = byArch[arch]
  if (!mapping) {
    console.warn(`[ensure-rollup-native] unsupported arch "${arch}", skipping`)
    return
  }

  const base = musl ? mapping.musl : mapping.gnu
  const pkg = `@rollup/rollup-${base}@${rollupVersion}`

  // If already present, no-op.
  try {
    require.resolve(`@rollup/rollup-${base}`)
    return
  } catch {
    // continue
  }

  console.log(`[ensure-rollup-native] installing ${pkg}`)
  const res = spawnSync(
    process.platform === 'win32' ? 'npm.cmd' : 'npm',
    ['i', '--no-save', '--no-audit', '--no-fund', '--no-package-lock', pkg],
    { stdio: 'inherit' },
  )

  if (res.status !== 0) {
    process.exitCode = res.status ?? 1
  }
}

main()

