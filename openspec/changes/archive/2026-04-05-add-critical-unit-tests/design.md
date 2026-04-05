## Context

目前專案的測試重心放在 API feature tests，已能驗證路由、request、controller 與 agent fake 的整體行為，但對 `NormalizeDetectedNames` 這類純邏輯元件，以及 agent 本身不依賴外部 provider 的穩定契約，仍缺少細粒度保護。這使得未來若調整姓名清洗規則、schema 結構或模型設定讀取方式時，容易只能依賴較厚重的 feature tests 來發現問題。

此變更不打算提高所有檔案的覆蓋率，而是聚焦在「一旦出錯就會直接影響辨識結果或 agent 契約」的核心單元。既有 feature tests 繼續負責驗證 API 層整體行為；新增的 unit tests 則負責提供更快速、明確的失敗訊號。

## Goals / Non-Goals

**Goals:**
- 為 `NormalizeDetectedNames` 建立獨立 unit tests，覆蓋正規化、稱謂去除、機構樣式過濾、重複值合併與 confidence 邊界處理。
- 為 `ChineseNameDetectionAgent` 與 `EmailScanAgent` 建立不依賴 provider 呼叫的契約測試，保護 instructions、provider、model、timeout 與 structured output schema。
- 明確區分 unit tests 與既有 feature tests 的責任邊界，避免重複測試同一層行為。

**Non-Goals:**
- 不新增新的對外 API 或調整既有 JSON 回應格式。
- 不測試實際 Ollama provider 線路或模型品質。
- 不追求全面 coverage 指標提升到特定百分比。

## Decisions

### 決策：優先補純邏輯與穩定契約，而不是 controller 細節單測
`NormalizeDetectedNames` 內含多段字串轉換與過濾規則，是目前最值得做單元測試的核心邏輯。相對而言，controller 主要負責接 request、呼叫 agent 並包裝 response，這些責任已由現有 feature tests 有效覆蓋。

替代方案：為 controller 與 form request 額外新增 unit tests。未採用原因是這會與既有 feature tests 高度重疊，卻無法大幅提升對規則型邏輯的保護。

### 決策：agent 單元測試聚焦「本地可驗證契約」
agent 類別應測試其 instructions 是否表達既定策略、provider/model/timeout 是否正確讀取，以及 schema 是否維持必要欄位與限制。這些都能在不呼叫外部模型的情況下快速驗證。

替代方案：完全仰賴 feature tests 間接涵蓋 agent。未採用原因是 feature tests 主要保護 API 行為，對 agent 類別本身的設定漂移不夠敏感。

### 決策：以少量高價值案例建立測試，不追求 exhaustive 組合
此次變更應優先挑選能代表規則邊界的案例，例如前後綴稱謂、機構尾碼、空 evidence、非數值 confidence 與重複姓名衝突。這能在維持測試可讀性的前提下，最大化回歸保護。

替代方案：一次補上大量資料集與組合測試。未採用原因是目前專案規模不大，過度展開會提高維護成本。

## Risks / Trade-offs

- [測試綁定實作細節過深] → 以公開方法與穩定契約為主，不直接耦合 private method 內部步驟。
- [agent instructions 文案調整造成測試脆弱] → 測試應聚焦關鍵語意與必要片段，而不是整段完全字串比對。
- [schema 驗證方式不清楚] → 實作前先確認 Laravel AI SDK 現有 schema 物件最穩定的斷言方式，再選擇最小可維護方案。
