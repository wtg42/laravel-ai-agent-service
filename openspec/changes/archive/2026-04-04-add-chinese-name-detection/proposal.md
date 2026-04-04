## Why

目前專案的核心目標是提供可本地部署的文件稽核與個資辨識能力，但尚未落地任何可驗證的 AI 辨識功能。先實作「從文字中偵測明確中文姓名」可作為最小可行能力，用來驗證 Laravel AI SDK、Ollama 與 Gemma 4 E2B 小模型在本地個資辨識場景中的可行性。

## What Changes

- 新增一個可從單段文字中抽取明確中文姓名的 API 能力。
- 以 Agent 產生候選姓名，並以 Laravel 端後處理收斂輸出結果，降低常見誤判。
- 定義穩定的結構化回應格式，供後續 Email 掃描與文件稽核能力重用。
- 建立中文姓名偵測的需求邊界與驗收標準，作為後續擴充其他個資類型的基礎。

## Capabilities

### New Capabilities
- `chinese-name-detection`: 從輸入文字中偵測明確中文姓名，回傳結構化偵測結果與必要的支持資訊。

### Modified Capabilities
無。

## Impact

- Affected code: `app/Ai/Agents/`、`app/Ai/Tools/`、`app/Http/Controllers/`、`routes/`
- API: 新增文字個資偵測相關 REST 端點
- Dependencies / systems: 使用既有 `laravel/ai` 套件與本地 Ollama 模型設定，不新增外部服務依賴
- Testing: 需要新增中文姓名偵測的 feature tests 與 Agent fake 測試案例
