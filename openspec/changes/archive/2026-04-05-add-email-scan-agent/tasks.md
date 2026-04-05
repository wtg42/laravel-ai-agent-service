## 1. Email 掃描核心能力

- [x] 1.1 建立 `EmailScanAgent`，定義以 Email 文字為輸入、`names` 為輸出的結構化 schema 與指示
- [x] 1.2 重用既有中文姓名偵測與正規化邏輯，讓 `EmailScanAgent` 第一版維持既有 precision-first 行為
- [x] 1.3 補齊 `EmailScanAgent` 的 provider、model 與失敗處理邊界，保持與現有 Ollama 設定一致

## 2. API 入口與應用層整合

- [x] 2.1 建立 Email 掃描請求驗證規則，拒絕空白或不可用的 Email 文字內容
- [x] 2.2 建立 Email 掃描 controller / action，串接 `EmailScanAgent` 並回傳穩定的 `names` 結構
- [x] 2.3 新增 Email 掃描 API 路由，並保留既有中文姓名偵測入口作為過渡期能力

## 3. 測試覆蓋

- [x] 3.1 新增 feature tests，驗證 Email 文字中的單一與多個姓名偵測成功情境
- [x] 3.2 新增測試覆蓋空結果、驗證錯誤與 provider 失敗時的可預期回應
- [x] 3.3 補充 Email 場景負樣本與重複姓名案例，確認稱謂、機構名稱與重複值處理正確

## 4. 驗證與收尾

- [x] 4.1 以 Pint 格式化所有 PHP 變更
- [x] 4.2 執行相關 Pest 測試並確認全部通過
- [x] 4.3 使用本地 Ollama 以代表性 Email 內容手動驗證已知限制與回應品質
