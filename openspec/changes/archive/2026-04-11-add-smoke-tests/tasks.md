## 1. 冒煙測試腳本

- [x] 1.1 建立 `bin/smoke.js`，設定 ESM module 格式與基本結構
- [x] 1.2 實作 Ollama 健康檢查（`GET /api/version`，失敗則輸出提示並 exit 1）
- [x] 1.3 實作 Laravel 健康檢查（`GET /up`，失敗則輸出提示並 exit 1）
- [x] 1.4 實作 email-scan 有效請求測試（驗證 200 + `names` 陣列存在）
- [x] 1.5 實作 email-scan 驗證錯誤測試（空白 content，驗證 422）
- [x] 1.6 實作 chinese-names/detect 有效請求測試（驗證 200 + `names` 陣列存在）
- [x] 1.7 實作 chinese-names/detect 驗證錯誤測試（空白 content，驗證 422）
- [x] 1.8 實作 adaptive-ocr 有效圖片請求測試（驗證 200 + `status` 與 `meta` 欄位存在）
- [x] 1.9 實作 adaptive-ocr 驗證錯誤測試（無圖片，驗證 422）
- [x] 1.10 實作測試結果摘要輸出與 exit code 處理（全過 exit 0，任一失敗 exit 1）

## 2. Justfile 整合

- [x] 2.1 在 `justfile` 新增 `smoke` recipe，執行 `node bin/smoke.js`
