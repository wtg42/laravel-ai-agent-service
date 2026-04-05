## Why

目前專案已落地中文姓名偵測，但整體產品方向其實是面向 Email 與文件中的個資掃描。先建立 `EmailScanAgent` 作為新的能力入口，並讓第一版只承接既有姓名偵測，可把架構重心從單一偵測器移向可逐步擴充的掃描 Agent，為後續加入身份證、電話與統編辨識鋪路。

## What Changes

- 新增一個以 Email 文字內容為輸入的 `EmailScanAgent` 能力，第一版只回傳姓名偵測結果。
- 將既有中文姓名偵測能力移植為 `EmailScanAgent` 的第一個掃描切片，保留既有 precision-first 與結構化輸出原則。
- 定義可擴充的 Email 掃描回應契約，使未來能在不破壞主要入口的前提下逐步加入其他個資類型。
- 明確區分「Email 掃描入口」與「姓名偵測子能力」的職責，讓後續是否採 Tool、子 Agent 或其他組合方式有清楚演進空間。

## Capabilities

### New Capabilities
- `email-scan`: 從 Email 文字內容掃描個資，第一版先回傳明確中文姓名的結構化結果，並保留未來擴充其他個資類型的空間。

### Modified Capabilities
- 無。

## Impact

- Affected code: `app/Ai/Agents/`、`app/Ai/Tools/`、`app/Http/Controllers/`、`app/Http/Requests/`、`routes/`
- API: 新增或調整 Email 掃描相關 REST 端點與回應格式
- Dependencies / systems: 持續使用既有 `laravel/ai`、Ollama 與本地模型設定，不新增外部依賴
- Testing: 需要新增 `EmailScanAgent` 的 feature tests，並重新整理現有姓名偵測整合測試的覆蓋邊界
