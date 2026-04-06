## Why

目前 `adaptive-ocr` API 在 macOS 本機開發環境中，使用 `php artisan serve` 搭配 Ollama 視覺模型處理較慢圖片時，常在模型完成前就發生 request 中斷，導致 client 只收到空回應。這讓同一份專案在不同開發環境的體驗不一致，也讓 OCR 能力難以穩定驗證與除錯。

## What Changes

- 調整 `adaptive-ocr` API 的執行時限策略，避免在本機長時間 OCR 請求中過早中斷。
- 將 OCR API 的 timeout 控制明確化，讓 Laravel 入口層與 Ollama provider timeout 協調一致。
- 保持變更範圍聚焦在 `adaptive-ocr` 路徑，避免不必要地放寬其他 API request 的執行限制。

## Capabilities

### New Capabilities

### Modified Capabilities
- `adaptive-ocr`: 調整 API 在長時間 OCR 請求下的執行時限與失敗行為，避免本機開發環境過早中斷造成空回應。

## Impact

- 影響 `app/Http/Controllers/Api/AdaptiveOcrController.php` 與其 request 入口執行行為。
- 可能影響與 Ollama 整合的 timeout 協調方式與本機開發測試流程。
- 不新增外部依賴，也不改變其他 PII API 的功能範圍。
