## 1. API Timeout 調整

- [x] 1.1 在 `AdaptiveOcrController` 中加入只作用於 `adaptive-ocr` 路由的 request 執行時限調整
- [x] 1.2 讓 request 執行時限與 `services.ollama.timeout` 設定協調一致，避免 HTTP 入口早於 provider timeout 終止

## 2. 失敗行為穩定化

- [x] 2.1 確認 `adaptive-ocr` 在慢速 OCR 情境下不再因 execution time limit 導致空回應
- [x] 2.2 確認其他 API 路由不會因這次變更而一起放寬 execution limit

## 3. 驗證

- [x] 3.1 補上或更新針對 `adaptive-ocr` request timeout 行為的測試
- [x] 3.2 使用本機 OCR API 實際驗證慢速圖片請求至少回傳正常結果或可控錯誤
