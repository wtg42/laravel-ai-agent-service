# Laravel AI Agent Service

一個以 **Laravel 13 + Laravel AI SDK** 為基底的本地 AI 辨識微服務，聚焦台灣常見的文件稽核與個資辨識場景。

## 專案概念

將常見的 AI 辨識需求拆解成一個個**獨立的 Agent 功能**，每個功能都有對應的 REST API 端點，可以單獨呼叫，也可以串接成業務流程。

**核心原則：**
- 每個辨識功能都是獨立的 Agent，關注點分離
- 使用本地 AI 模型（Ollama + Gemma），敏感資料不外傳
- 業務驗證邏輯（身份證 checksum 等）用 Tool 實作，不依賴 AI 猜測
- 純 REST API，方便整合其他系統

## 技術棧

| 層級 | 技術 |
|------|------|
| 框架 | Laravel 13 |
| AI SDK | `laravel/ai ^0.4.3` |
| AI 模型 | Ollama + Gemma 4 E2B（本地部署） |
| 資料庫 | SQLite（開發） |
| 測試 | Pest v4 |
| 格式化 | Laravel Pint |

## 功能規劃

### 已實作

| 功能 | Agent | 說明 |
|------|-------|------|
| 中文姓名偵測 | `ChineseNameDetectionAgent` | 從文字中抽取明確中文姓名，回傳 `value`、`evidence`、`confidence` |
| Email 掃描（v1） | `EmailScanAgent` | 從 Email 文字掃描明確中文姓名，統一由 `names` 欄位回傳結果 |

### 規劃中

| 功能 | Agent | 說明 |
|------|-------|------|
| Email 掃描擴充欄位 | `EmailScanAgent` | 後續擴充身份證字號、電話、統編等其他個資類型 |
| 影像 OCR | `OcrAgent` | 上傳圖片，提取圖中文字（需視覺模型） |
| 圖片身份證辨識 | `IdentityDocumentAgent` | 從身份證圖片結構化擷取資料 |
| 業務流程組合 | `DocumentProcessingAgent` | 組合多個 Agent 成一個稽核流程 |

## 目前架構

```
請求
  │
  ▼
REST API 端點（routes/api.php）
  │
  ▼
Form Request（app/Http/Requests/）
  │  └─ 驗證輸入內容
  ▼
Controller（app/Http/Controllers/Api/）
  │  ├─ EmailScanController
  │  ├─ ChineseNameDetectionController
  │  └─ 組合 JSON 回應
  ▼
Agent（app/Ai/Agents/）
  │  ├─ EmailScanAgent
  │  ├─ ChineseNameDetectionAgent
  │  ├─ 定義 AI 指示（system prompt）
  │  ├─ 指定 Ollama provider / model
  │  └─ 定義 structured output schema
  │
  ▼
Ollama（本地 Gemma 4 E2B / Gemma 4 E2B Q4）
  │
  ▼
Tools（app/Ai/Tools/）
  └─ 去重、稱謂剝除、明顯非姓名詞過濾
  │
  ▼
結構化 JSON 回應
```

```
app/
├── Ai/
│   ├── Agents/
│   │   ├── ChineseNameDetectionAgent.php
│   │   └── EmailScanAgent.php
│   └── Tools/
│       └── NormalizeDetectedNames.php
├── Http/
│   ├── Controllers/Api/
│   │   ├── ChineseNameDetectionController.php
│   │   └── EmailScanController.php
│   └── Requests/
│       ├── DetectChineseNamesRequest.php
│       └── ScanEmailRequest.php
routes/
└── api.php
tests/
└── Feature/
    ├── ChineseNameDetectionApiTest.php
    └── EmailScanApiTest.php
```

## API 範例

```bash
# Email 掃描（目前較貼近產品方向的主入口，v1 只回傳 names）
POST /api/pii/email-scan
Content-Type: application/json

{
  "content": "寄件人：王小明。請與陳怡君確認報價。"
}

# 回應
{
  "names": [
    {
      "value": "王小明",
      "evidence": "寄件人：王小明",
      "confidence": 0.81
    },
    {
      "value": "陳怡君",
      "evidence": "請與陳怡君確認報價。",
      "confidence": 0.89
    }
  ]
}

# 中文姓名偵測（既有獨立能力，保留作為過渡期入口）
POST /api/pii/chinese-names/detect
Content-Type: application/json

{
  "content": "您好，我是王小明，今天與陳怡君一起出席會議。"
}

# 回應
{
  "names": [
    {
      "value": "王小明",
      "evidence": "您好，我是王小明",
      "confidence": 1
    },
    {
      "value": "陳怡君",
      "evidence": "今天與陳怡君一起出席會議",
      "confidence": 1
    }
  ]
}
```

## 環境設定

```bash
# 安裝依賴
composer install

# 設定環境
cp .env.example .env
php artisan key:generate

# 啟用 API route（若尚未有 routes/api.php，可參考 Laravel install:api 文件）

# 資料庫
php artisan migrate

# 啟動 Ollama（另開終端）
ollama serve

# 執行（開發）
composer run dev
```

**Ollama 設定（`.env`）：**
```
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=gemma4-e2b-q4:latest
OLLAMA_TIMEOUT=60
```

**拉取模型範例：**

```bash
ollama pull gemma4-e2b-q4:latest
```

## 測試方式

### 1. 執行自動化測試

```bash
php artisan test --compact
php artisan test --compact tests/Feature/ChineseNameDetectionApiTest.php
php artisan test --compact tests/Feature/EmailScanApiTest.php
```

目前 feature tests 已覆蓋：
- `ChineseNameDetectionApiTest`
  - 成功偵測多個中文姓名與重複值正規化
  - 空結果回應
  - 空白輸入驗證錯誤
  - 稱謂與機構名稱誤判過濾
  - Ollama / provider 失敗時回傳 `503`
- `EmailScanApiTest`
  - 單一與多個中文姓名偵測
  - 空結果回應
  - 空白 Email 內容驗證錯誤
  - 稱謂、機構名稱與重複值過濾
  - Ollama / provider 失敗時回傳 `503`

### 2. 手動測試 API

先確認 Laravel 與 Ollama 都已啟動，再使用 `curl`：

```bash
# Email 掃描（v1）
curl -X POST http://127.0.0.1:8000/api/pii/email-scan \
  -H "Content-Type: application/json" \
  -d '{
    "content": "寄件人：王小明。請與陳怡君確認報價。"
  }'

# 中文姓名偵測
curl -X POST http://127.0.0.1:8000/api/pii/chinese-names/detect \
  -H "Content-Type: application/json" \
  -d '{
    "content": "出席者包含王小明、陳怡君、李承翰。"
  }'
```

負樣本測試：

```bash
curl -X POST http://127.0.0.1:8000/api/pii/chinese-names/detect \
  -H "Content-Type: application/json" \
  -d '{
    "content": "林務局與財務部將於明日公告。"
  }'
```

欄位型輸入測試：

```bash
curl -X POST http://127.0.0.1:8000/api/pii/email-scan \
  -H "Content-Type: application/json" \
  -d '{
    "content": "聯絡窗口：李美玲。李美玲老師已確認出席。"
  }'
```

### 3. 目前已知限制

- 目前策略偏 `precision-first`，因此可能漏抓部分姓名
- `EmailScanAgent` v1 目前只回傳 `names`，尚未支援身份證字號、電話、統編、附件或 OCR
- 名單型句子表現較好，欄位型多姓名輸入仍可能漏抓
- `confidence` 目前主要來自模型輸出，不代表嚴格統計分數

## 開發指令

```bash
php artisan test --compact        # 執行測試
vendor/bin/pint --dirty           # 格式化修改過的檔案
php artisan make:agent FooAgent   # 建立新 Agent
php artisan make:tool FooTool     # 建立新 Tool
ollama serve                      # 啟動本地 Ollama service
```
