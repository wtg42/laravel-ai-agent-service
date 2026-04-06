## Context

目前 `adaptive-ocr` 會透過 Laravel API 同步呼叫 Ollama 視覺模型處理圖片。實際測試顯示，在 macOS 本機使用 `php artisan serve` 時，某些 OCR 請求會在模型仍在執行時被 request 執行時限中止，最終讓 client 只收到空回應；同一份專案在 Linux 本機則可能成功完成。這表示問題更接近本機 HTTP 入口執行環境與 OCR 請求時限不一致，而非單純 provider timeout 設定不足。

## Goals / Non-Goals

**Goals:**
- 讓 `adaptive-ocr` API 在本機較慢的 OCR 請求下，不會因 request 執行時限過短而直接中斷。
- 讓 API 的 request 執行時間與 `OLLAMA_TIMEOUT` 的預期協調一致。
- 將變更限制在 `adaptive-ocr` 路徑，避免對其他 API 造成不必要影響。

**Non-Goals:**
- 不重新設計 Adaptive OCR workflow。
- 不修改其他 AI API 的 timeout 策略。
- 不更換本機開發伺服器或導入新基礎設施。

## Decisions

### 1. 在 `AdaptiveOcrController` 入口調整 request 執行時限
將 request 執行時限的調整放在 `app/Http/Controllers/Api/AdaptiveOcrController.php`，在進入 workflow 前針對當前請求提高執行上限。這樣可以把影響範圍限制在單一路由，避免把 `public/index.php` 的全域入口一起放寬。

替代方案：在 `public/index.php` 全域放寬 execution time。這雖然更集中，但會影響所有本機 HTTP request，與這次只處理 OCR 路徑的需求不符。

### 2. 以應用程式設定作為 OCR timeout 的單一來源
控制器提高 request 執行時限時，應以既有的 `services.ollama.timeout` 為基準，必要時保留少量緩衝，避免 HTTP 入口比 provider timeout 更早終止。

替代方案：新增另一組專用 execution timeout 設定。這會讓 timeout 來源分散，增加理解與維護成本；第一版以既有設定為主較簡單且可預期。

### 3. 保持既有失敗語意，但避免空回應
本次變更不改變 `adaptive-ocr` 既有成功資料格式；若 OCR 仍因 provider 錯誤或逾時失敗，API 應維持可預測的應用程式回應，而不是因 PHP 執行被硬性中止而讓 client 收到空回應。

替代方案：將 OCR 改為背景 job 或非同步輪詢。這能徹底避開長請求問題，但超出此次暫時穩定本機 API 的範圍。

## Risks / Trade-offs

- [風險] 控制器局部調整 execution time 仍可能不足以涵蓋所有慢模型情境 -> [緩解] 以 `services.ollama.timeout` 為基準並保留緩衝，讓 request 上限至少不早於 provider timeout。
- [風險] 本機開發與正式部署環境的行為差異仍可能存在 -> [緩解] 將變更限定在 API 入口與設定協調，不依賴特定作業系統判斷。
- [取捨] 這是同步請求的穩定化措施，不是長期架構最佳解 -> [緩解] 保持實作最小，後續若需要可再評估非同步 OCR 流程。
