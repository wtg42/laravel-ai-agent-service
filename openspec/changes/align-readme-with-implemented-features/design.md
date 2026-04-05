## Context

目前專案已經同時提供中文姓名偵測入口與 Email 掃描入口，但 `README.md` 仍以較早期的單一能力切片為主，導致功能清單、架構圖、API 範例與測試說明都與實際程式結構不一致。這次變更不涉及產品功能擴充，而是要把現有文件修正成能正確描述既有實作狀態，避免開發、測試與整合時產生誤導。

## Goals / Non-Goals

**Goals:**
- 讓 `README.md` 準確反映目前已存在的 Agent、API 路由、request / controller / test 檔案與測試覆蓋。
- 明確區分已實作能力與仍在規劃中的能力，避免把 `EmailScanAgent` 誤列為未完成。
- 在不更動程式行為的前提下，讓 README 成為目前專案狀態的可信入口文件。

**Non-Goals:**
- 不新增或修改任何應用程式邏輯、Prompt、Tool 或 API 契約。
- 不重寫 README 的整體定位或品牌敘事，只修正與現況不一致的區塊。
- 不替尚未實作的 OCR、身份證辨識或工作流程組合能力補寫過度具體的承諾。

## Decisions

### 1. 以現有程式碼與封存 change 為單一真相來源
README 的修正內容應以目前 `routes/api.php`、`app/Ai/Agents/`、`app/Http/Controllers/Api/`、`app/Http/Requests/`、`tests/Feature/`，以及已封存的 OpenSpec change 為準，而不是延續舊版 README 的敘事。這可避免文件再次複製過時資訊。

替代方案：只局部修正文句，不重新比對實際檔案與 OpenSpec。這樣較快，但容易遺漏 API、測試或檔案樹等其他不同步區塊。

### 2. 將 `EmailScanAgent` 視為已實作能力，但明確標示 v1 邊界
README 應把 Email 掃描從「規劃中」移到「已實作」，同時說明第一版只輸出 `names`，尚未支援身份證字號、電話、統編與附件處理。這能同時反映實作現況與產品邊界。

替代方案：維持「規劃中」以保留完整產品願景。這會讓文件與實際可用 API 不一致，反而增加理解成本。

### 3. 保留雙入口敘事，說明主入口與既有能力並存
README 應同時描述 `POST /api/pii/email-scan` 與 `POST /api/pii/chinese-names/detect`，並說明前者是較貼近產品方向的掃描入口，後者是既有能力與過渡期入口。這與現有 change 設計一致，也較符合現在路由狀態。

替代方案：README 只保留 Email 掃描入口。這樣較簡潔，但會隱藏目前仍存在且可用的中文姓名偵測 API。

### 4. 測試與檔案樹只記錄目前存在的真實覆蓋
README 中的檔案樹、測試列表與手動驗證範例應只描述 repository 內已存在的檔案與測試案例，不預先承諾尚未建立的測試層次或能力。這能保持文件可驗證、可維護。

替代方案：用較抽象的描述避免列具體檔名。這會降低文件的可操作性，對新加入專案的人幫助較小。

## Risks / Trade-offs

- [README 內容仍可能再次落後於程式碼] → 以 routes、tests 與封存 change 為依據更新，降低本次遺漏風險。
- [同時描述雙入口會增加篇幅] → 在 README 中明確標示主入口與既有入口的角色差異。
- [文件過度強調未來規劃會模糊已實作範圍] → 對規劃中能力只保留高層描述，並避免寫出尚未存在的 API 細節。

## Migration Plan

1. 盤點 README 與現有 codebase / OpenSpec 的差異。
2. 更新功能清單、架構圖、檔案樹、API 範例、測試說明與已知限制。
3. 重新人工檢查 README 是否與目前 routes、controllers、requests、agents、tests 一致。

## Open Questions

- README 是否要明確將 `EmailScanAgent` 描述為新的主要入口，或只描述為另一個已實作端點。
- 是否要在 README 中保留中文姓名偵測 API 的完整 `curl` 範例，還是縮減為較短的補充說明。
