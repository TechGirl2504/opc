import fs from 'node:fs'
import zlib from 'node:zlib'

function crc32(buf) {
  // CRC32 (IEEE 802.3)
  let crc = 0xffffffff
  for (let i = 0; i < buf.length; i++) {
    crc ^= buf[i]
    for (let j = 0; j < 8; j++) {
      const mask = -(crc & 1)
      crc = (crc >>> 1) ^ (0xedb88320 & mask)
    }
  }
  return (crc ^ 0xffffffff) >>> 0
}

function chunk(type, data) {
  const typeBuf = Buffer.from(type, 'ascii')
  const lenBuf = Buffer.alloc(4)
  lenBuf.writeUInt32BE(data.length, 0)
  const crcBuf = Buffer.alloc(4)
  const crc = crc32(Buffer.concat([typeBuf, data]))
  crcBuf.writeUInt32BE(crc, 0)
  return Buffer.concat([lenBuf, typeBuf, data, crcBuf])
}

function makeSolidPng(width, height, rgba) {
  const sig = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a])

  // IHDR: 13 bytes
  const ihdr = Buffer.alloc(13)
  ihdr.writeUInt32BE(width, 0)
  ihdr.writeUInt32BE(height, 4)
  ihdr.writeUInt8(8, 8) // bit depth
  ihdr.writeUInt8(6, 9) // color type: RGBA
  ihdr.writeUInt8(0, 10) // compression
  ihdr.writeUInt8(0, 11) // filter
  ihdr.writeUInt8(0, 12) // interlace

  // Raw image data: each row starts with filter byte 0 (None)
  const rowLen = 1 + width * 4
  const raw = Buffer.alloc(rowLen * height)
  for (let y = 0; y < height; y++) {
    const rowStart = y * rowLen
    raw[rowStart] = 0
    for (let x = 0; x < width; x++) {
      const p = rowStart + 1 + x * 4
      raw[p + 0] = rgba[0]
      raw[p + 1] = rgba[1]
      raw[p + 2] = rgba[2]
      raw[p + 3] = rgba[3]
    }
  }

  const compressed = zlib.deflateSync(raw, { level: 9 })

  return Buffer.concat([sig, chunk('IHDR', ihdr), chunk('IDAT', compressed), chunk('IEND', Buffer.alloc(0))])
}

const primary = [0x19, 0x76, 0xd2, 0xff] // #1976d2

const out = [
  { file: 'public/pwa-192x192.png', w: 192, h: 192 },
  { file: 'public/pwa-512x512.png', w: 512, h: 512 },
  { file: 'public/apple-touch-icon.png', w: 180, h: 180 },
]

for (const f of out) {
  const buf = makeSolidPng(f.w, f.h, primary)
  fs.writeFileSync(f.file, buf)
  // eslint-disable-next-line no-console
  console.log(`Generated ${f.file} (${f.w}x${f.h}) - ${buf.length} bytes`)
}


