## Why

目前專案的主要驗證集中在 API feature tests，對核心純邏輯元件的單元測試覆蓋仍然偏低。隨著姓名正規化與 AI agent 設定逐步擴充，缺少聚焦的 unit tests 會讓小幅重構也變得不容易判斷風險。

## What Changes

- 新增一個聚焦於核心純邏輯與 agent 契約的單元測試變更提案。
- 定義哪些元件必須補上獨立 unit tests，特別是 `NormalizeDetectedNames` 的正規化、過濾、去重與信心值處理。
- 定義 agent 類別中不依賴外部 provider 的穩定契約測試範圍，例如指示文字、provider、model、timeout 與 structured schema。
- 保持既有 feature tests 作為 API 行為驗證，將 unit tests 補到較適合細粒度保護的層級。

## Capabilities

### New Capabilities
- `critical-unit-tests`: 定義專案中關鍵純邏輯與 agent 契約應具備的單元測試保護範圍

### Modified Capabilities

## Impact

- 受影響測試：`tests/Unit/`
- 受影響程式碼：`app/Ai/Tools/NormalizeDetectedNames.php`、`app/Ai/Agents/ChineseNameDetectionAgent.php`、`app/Ai/Agents/EmailScanAgent.php`
- 不影響對外 API 路由與 JSON 契約，但會提升未來重構時的回歸保護能力
