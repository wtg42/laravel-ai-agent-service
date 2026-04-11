#!/usr/bin/env node

const BASE_URL = 'http://localhost:8000'
const OLLAMA_URL = 'http://localhost:11434'

// Minimal 1x1 white PNG — no external tools required
const MINIMAL_PNG = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
    'base64'
)

const SPINNER_FRAMES = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏']

const results = []

function createSpinner(name) {
    let i = 0
    process.stdout.write(`  ${SPINNER_FRAMES[0]} ${name}`)
    const timer = setInterval(() => {
        process.stdout.write(`\r  ${SPINNER_FRAMES[i++ % SPINNER_FRAMES.length]} ${name}`)
    }, 80)
    return {
        succeed() {
            clearInterval(timer)
            process.stdout.write(`\r  ✓ ${name}\n`)
        },
        fail(reason) {
            clearInterval(timer)
            process.stdout.write(`\r  ✗ ${name}: ${reason}\n`)
        },
    }
}

async function checkService(url, name) {
    try {
        const response = await fetch(url, { signal: AbortSignal.timeout(2000) })
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`)
        }
    } catch {
        console.error(`\n[ERROR] ${name} 未啟動或無法連線`)
        console.error(`        請先啟動 ${name} 後再執行 just smoke\n`)
        process.exit(1)
    }
}

async function run(name, fn) {
    const spinner = createSpinner(name)
    try {
        const result = await fn()
        if (result.ok) {
            spinner.succeed()
            results.push({ name, ok: true })
        } else {
            spinner.fail(result.reason)
            results.push({ name, ok: false, reason: result.reason })
        }
    } catch (e) {
        const reason = e.name === 'TimeoutError' ? '請求逾時' : e.message
        spinner.fail(reason)
        results.push({ name, ok: false, reason })
    }
}

async function testEmailScanValid() {
    const response = await fetch(`${BASE_URL}/api/pii/email-scan`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ content: '您好，我是王小明，負責這次專案。' }),
        signal: AbortSignal.timeout(90_000),
    })
    const body = await response.json()
    if (response.status !== 200) return { ok: false, reason: `預期 200，得到 ${response.status}` }
    if (!Array.isArray(body.names)) return { ok: false, reason: '回應缺少 names 陣列' }
    return { ok: true }
}

async function testEmailScanValidation() {
    const response = await fetch(`${BASE_URL}/api/pii/email-scan`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ content: '   ' }),
        signal: AbortSignal.timeout(10_000),
    })
    if (response.status !== 422) return { ok: false, reason: `預期 422，得到 ${response.status}` }
    return { ok: true }
}

async function testChineseNamesValid() {
    const response = await fetch(`${BASE_URL}/api/pii/chinese-names/detect`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ content: '今天與陳怡君一起出席會議。' }),
        signal: AbortSignal.timeout(90_000),
    })
    const body = await response.json()
    if (response.status !== 200) return { ok: false, reason: `預期 200，得到 ${response.status}` }
    if (!Array.isArray(body.names)) return { ok: false, reason: '回應缺少 names 陣列' }
    return { ok: true }
}

async function testChineseNamesValidation() {
    const response = await fetch(`${BASE_URL}/api/pii/chinese-names/detect`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ content: '   ' }),
        signal: AbortSignal.timeout(10_000),
    })
    if (response.status !== 422) return { ok: false, reason: `預期 422，得到 ${response.status}` }
    return { ok: true }
}

async function testAdaptiveOcrValid() {
    const form = new FormData()
    const blob = new Blob([MINIMAL_PNG], { type: 'image/png' })
    form.append('image', blob, 'document.png')

    const response = await fetch(`${BASE_URL}/api/pii/adaptive-ocr`, {
        method: 'POST',
        headers: { Accept: 'application/json' },
        body: form,
        signal: AbortSignal.timeout(150_000),
    })
    const body = await response.json()
    if (response.status !== 200) return { ok: false, reason: `預期 200，得到 ${response.status}` }
    if (!body.status) return { ok: false, reason: '回應缺少 status 欄位' }
    if (!body.meta) return { ok: false, reason: '回應缺少 meta 欄位' }
    return { ok: true }
}

async function testAdaptiveOcrValidation() {
    const response = await fetch(`${BASE_URL}/api/pii/adaptive-ocr`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({}),
        signal: AbortSignal.timeout(10_000),
    })
    if (response.status !== 422) return { ok: false, reason: `預期 422，得到 ${response.status}` }
    return { ok: true }
}

async function main() {
    console.log('Checking services...')
    await checkService(`${OLLAMA_URL}/api/version`, 'Ollama')
    await checkService(`${BASE_URL}/up`, 'Laravel')
    console.log('  ✓ Ollama')
    console.log('  ✓ Laravel')

    console.log('\nRunning smoke tests...')

    await run('email-scan 有效請求', testEmailScanValid)
    await run('email-scan 驗證錯誤', testEmailScanValidation)
    await run('chinese-names 有效請求', testChineseNamesValid)
    await run('chinese-names 驗證錯誤', testChineseNamesValidation)
    await run('adaptive-ocr 有效圖片', testAdaptiveOcrValid)
    await run('adaptive-ocr 驗證錯誤', testAdaptiveOcrValidation)

    const passed = results.filter((r) => r.ok).length
    const failed = results.filter((r) => !r.ok).length

    console.log(`\n${passed}/${results.length} tests passed`)

    if (failed > 0) {
        process.exit(1)
    }
}

main().catch((e) => {
    console.error('\n[UNEXPECTED ERROR]', e.message)
    process.exit(1)
})
