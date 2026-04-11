## Context

專案目前所有自動化測試皆使用 `Agent::fake()` mock AI 呼叫，因此無法在真實環境下驗證 Ollama 整合是否正常。開發時需要一個輕量工具，能快速確認 Ollama 與 Laravel 服務都在線，並對全部 PII API 端點發出真實請求、驗證回應結構是否符合預期。

## Goals / Non-Goals

**Goals:**
- 提供可由 `just smoke` 一鍵執行的冒煙測試
- 在測試前檢查 Ollama 與 Laravel 服務健康狀態，未就緒則提示並退出
- 驗證所有 PII API 端點的 HTTP 狀態碼與回應結構
- 以 Node.js 撰寫，利用原生 `fetch` 與 `FormData`

**Non-Goals:**
- 驗證 AI 模型的輸出內容正確性（非決定性，不適合自動化斷言）
- 自動啟動或關閉 Ollama / Laravel 服務
- 取代現有的 Pest 單元與功能測試

## Decisions

### 使用 HTTP health check 而非 `ps` 檢查服務狀態

偵測服務是否就緒應以 HTTP 回應為準，而非程序是否存在。程序存在並不代表服務已完全啟動並可接受請求。

- Ollama：`GET http://localhost:11434/api/version`（與 `bin/dev` 一致）
- Laravel：`GET http://localhost:8000/up`（Laravel 內建 health check endpoint）

### 使用 Node.js 而非 bash

專案已有 Node.js 環境（Vite 建置工具）。Node.js 原生 `fetch`（Node 18+）與 `FormData` 對 multipart 圖片上傳的處理比 bash curl 更可靠、更易維護，斷言邏輯也更易讀。

### 不自行 spawn / kill 服務

避免與開發者已啟動的服務衝突（例如 `just dev` 已在跑）。冒煙測試腳本的職責是測試，不是服務管理。

### 只驗結構，不驗 AI 內容

Ollama 的輸出為非決定性，對內容的斷言會導致測試 flaky。冒煙測試驗證：
- HTTP 狀態碼符合預期（200 / 422）
- 回應 JSON 包含必要的頂層欄位（`names`、`status`、`text`、`meta`）

## Risks / Trade-offs

- **測試速度慢**：呼叫真實 Ollama 需要數秒至數十秒 → 可接受，冒煙測試本不要求快速
- **Ollama 回應不穩定**：模型可能因資源不足超時 → 腳本對 HTTP 錯誤給出明確訊息，不視為測試失敗
- **AdaptiveOCR 需要圖片**：使用 ImageMagick 即時生成最小測試圖片（與現有 Pest 測試一致）→ 若 ImageMagick 未安裝則跳過並提示
