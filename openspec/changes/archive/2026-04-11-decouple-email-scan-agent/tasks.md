## 1. 重構 EmailScanAgent

- [x] 1.1 將 `EmailScanAgent` 改為直接實作 `Agent`、`HasStructuredOutput`，使用 `Promptable` trait，移除 `extends ChineseNameDetectionAgent`
- [x] 1.2 在 `EmailScanAgent` 加入 `model()` 與 `timeout()` 方法（從 config 讀取）
- [x] 1.3 在 `EmailScanAgent` 加入 `schema()` 方法（names 陣列定義）
- [x] 1.4 確認 `EmailScanAgent` 已有 `#[Provider(Lab::Ollama)]` attribute（先前已加，確認保留）

## 2. 更新測試

- [x] 2.1 在 `EmailScanAgentTest` 補上 `model()` 與 `timeout()` 的 config 覆寫測試
- [x] 2.2 在 `EmailScanAgentTest` 補上 `schema()` 結構驗證測試

## 3. 驗證

- [x] 3.1 執行 `php artisan test --compact` 確認全部通過
