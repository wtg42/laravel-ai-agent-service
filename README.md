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
| 資料庫 | SQLite（開發）|
| 測試 | Pest v4 |
| 格式化 | Laravel Pint |

## 功能規劃

### 已實作

> 尚未開始

### 規劃中

| 功能 | Agent | 說明 |
|------|-------|------|
| Email 掃描個資 | `EmailScanAgent` | 從 Email 文字辨識姓名、身份證字號、電話、統編等 |
| 影像 OCR | `OcrAgent` | 上傳圖片，提取圖中文字（需視覺模型） |
| 圖片身份證辨識 | `IdentityDocumentAgent` | 從身份證圖片結構化擷取資料 |
| 業務流程組合 | `DocumentProcessingAgent` | 組合多個 Agent 成一個稽核流程 |

## 架構說明

```
請求
  │
  ▼
REST API 端點（routes/api.php）
  │
  ▼
Agent（app/Ai/Agents/）
  │  ├─ 定義 AI 指示（system prompt）
  │  └─ 載入 Tools
  │
  ▼
Ollama（本地 Gemma 4 E2B）
  │
  ▼
Tools（app/Ai/Tools/）─ 格式驗證、規則判斷
  │
  ▼
結構化 JSON 回應
```

```
app/
├── Ai/
│   ├── Agents/     ← 每個辨識場景一個 Agent
│   └── Tools/      ← 驗證工具（身份證格式、電話格式等）
routes/
└── api.php         ← REST API 端點
```

## API 範例

```bash
# Email 掃描個資
POST /api/email/scan
Content-Type: application/json

{
  "content": "您好，我是王小明，身份證字號 A123456789，聯絡電話 0912-345-678..."
}

# 回應
{
  "names": ["王小明"],
  "id_numbers": [{ "value": "A123456789", "valid": true }],
  "phones": ["0912-345-678"],
  "emails": [],
  "unified_ids": []
}
```

## 環境設定

```bash
# 安裝依賴
composer install

# 設定環境
cp .env.example .env
php artisan key:generate

# 資料庫
php artisan migrate

# 執行（開發）
composer run dev
```

**Ollama 設定（`.env`）：**
```
AI_DEFAULT_DRIVER=ollama
OLLAMA_MODEL=gemma4:e2b
```

## 開發指令

```bash
php artisan test --compact        # 執行測試
vendor/bin/pint --dirty           # 格式化修改過的檔案
php artisan make:agent FooAgent   # 建立新 Agent
php artisan make:tool FooTool     # 建立新 Tool
```
