## Purpose

Define the smoke test suite that validates all critical API endpoints are functioning correctly in a live environment, with service health checks before any tests are executed.

## Requirements

### Requirement: 冒煙測試在執行前驗證服務健康狀態
系統 SHALL 在執行任何 API 測試前，透過 HTTP 請求確認 Ollama 與 Laravel 服務均已就緒。任一服務未回應時 MUST 輸出明確訊息並以非零 exit code 退出。

#### Scenario: Ollama 服務未啟動時退出
- **WHEN** 執行 `just smoke` 但 Ollama 未在 `http://localhost:11434` 回應
- **THEN** 腳本輸出提示訊息說明 Ollama 未啟動並以 exit code 1 退出，不執行任何 API 測試

#### Scenario: Laravel 服務未啟動時退出
- **WHEN** 執行 `just smoke` 但 Laravel 未在 `http://localhost:8000/up` 回應
- **THEN** 腳本輸出提示訊息說明 Laravel 未啟動並以 exit code 1 退出，不執行任何 API 測試

#### Scenario: 兩個服務均就緒時繼續執行測試
- **WHEN** Ollama 與 Laravel 均正常回應
- **THEN** 腳本繼續執行所有 API 測試

### Requirement: 冒煙測試驗證 email-scan 端點回應結構
系統 SHALL 對 `/api/pii/email-scan` 發送含有效內容的 POST 請求，並驗證回應狀態碼與結構。

#### Scenario: 有效請求回傳正確結構
- **WHEN** 以包含中文文字的 `content` 欄位發送 POST 請求至 `/api/pii/email-scan`
- **THEN** 回應 HTTP 狀態碼為 200 且回應 JSON 包含 `names` 陣列

#### Scenario: 空白內容回傳驗證錯誤
- **WHEN** 以空白 `content` 欄位發送 POST 請求至 `/api/pii/email-scan`
- **THEN** 回應 HTTP 狀態碼為 422

### Requirement: 冒煙測試驗證 chinese-names/detect 端點回應結構
系統 SHALL 對 `/api/pii/chinese-names/detect` 發送含有效內容的 POST 請求，並驗證回應狀態碼與結構。

#### Scenario: 有效請求回傳正確結構
- **WHEN** 以包含中文文字的 `content` 欄位發送 POST 請求至 `/api/pii/chinese-names/detect`
- **THEN** 回應 HTTP 狀態碼為 200 且回應 JSON 包含 `names` 陣列

#### Scenario: 空白內容回傳驗證錯誤
- **WHEN** 以空白 `content` 欄位發送 POST 請求至 `/api/pii/chinese-names/detect`
- **THEN** 回應 HTTP 狀態碼為 422

### Requirement: 冒煙測試驗證 adaptive-ocr 端點回應結構
系統 SHALL 對 `/api/pii/adaptive-ocr` 發送含圖片的 multipart POST 請求，並驗證回應狀態碼與結構。

#### Scenario: 有效圖片請求回傳正確結構
- **WHEN** 以有效圖片檔案發送 POST 請求至 `/api/pii/adaptive-ocr`
- **THEN** 回應 HTTP 狀態碼為 200 且回應 JSON 包含 `status` 與 `meta` 欄位

#### Scenario: 未提供圖片回傳驗證錯誤
- **WHEN** 發送不含 `image` 欄位的 POST 請求至 `/api/pii/adaptive-ocr`
- **THEN** 回應 HTTP 狀態碼為 422

### Requirement: 冒煙測試輸出清楚的通過或失敗摘要
系統 SHALL 在所有測試完成後輸出每個測試項目的結果，並以 exit code 反映整體通過與否。

#### Scenario: 全部測試通過
- **WHEN** 所有 API 測試均符合預期
- **THEN** 腳本輸出每個測試通過的訊息並以 exit code 0 退出

#### Scenario: 任一測試失敗
- **WHEN** 任一 API 測試不符合預期
- **THEN** 腳本輸出失敗的測試項目與原因，並以 exit code 1 退出
