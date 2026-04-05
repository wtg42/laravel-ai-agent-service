## ADDED Requirements

### Requirement: 系統 MUST 為姓名正規化核心邏輯提供單元測試保護
系統 MUST 為 `NormalizeDetectedNames` 的公開正規化行為提供獨立 unit tests，以保護姓名值清洗、假陽性過濾、重複值合併與輸出穩定性。

#### Scenario: 去除稱謂與空白後保留有效姓名
- **WHEN** 正規化輸入包含前綴欄位稱謂、後綴職稱或多餘空白，但核心姓名仍為有效中文姓名
- **THEN** 單元測試會驗證輸出保留清洗後的有效姓名值

#### Scenario: 過濾非姓名與機構樣式詞
- **WHEN** 正規化輸入包含職稱稱呼、部門名稱或帶有機構尾碼的詞
- **THEN** 單元測試會驗證這些值不會出現在最終輸出中

#### Scenario: 合併重複姓名並保留最高信心值
- **WHEN** 正規化輸入中同一姓名以不同形式重複出現，且 confidence 值不同
- **THEN** 單元測試會驗證輸出只保留一筆該姓名，並採用最高 confidence 值

#### Scenario: 不可用 evidence 與 confidence 仍產生穩定結果
- **WHEN** 正規化輸入缺少 evidence、提供空字串，或 confidence 為非數值與超出邊界值
- **THEN** 單元測試會驗證輸出仍回傳穩定字串 evidence，且 confidence 被限制在 0 到 1 之間

### Requirement: 系統 MUST 為 agent 類別提供本地契約單元測試
系統 MUST 為 `ChineseNameDetectionAgent` 與 `EmailScanAgent` 提供不依賴外部模型呼叫的 unit tests，以保護其 instructions、provider、model、timeout 與 structured output schema 契約。

#### Scenario: agent 公布既定 provider 與模型設定來源
- **WHEN** 單元測試建立 agent 實例並讀取 provider、model 與 timeout 設定
- **THEN** 測試會驗證 agent 使用既定 provider，且 model 與 timeout 來自應用程式設定

#### Scenario: agent instructions 維持既定辨識策略
- **WHEN** 單元測試讀取 agent instructions
- **THEN** 測試會驗證內容包含 precision-first、排除非姓名詞與空結果策略等關鍵語意

#### Scenario: agent schema 維持 names 結構契約
- **WHEN** 單元測試檢查 agent 的 structured output schema
- **THEN** 測試會驗證 schema 仍要求 `names` 陣列以及每筆結果的 `value`、`evidence`、`confidence` 欄位
