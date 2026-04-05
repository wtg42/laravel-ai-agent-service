## Why

目前 `README.md` 仍停留在較早期的專案狀態，尚未反映已經完成的 `EmailScanAgent`、對外 API 入口與對應測試覆蓋。這會讓新加入的開發者或整合方誤判系統現況，因此需要先把專案文件與既有實作對齊。

## What Changes

- 更新 `README.md` 的功能清單，將已完成的 Email 掃描能力從規劃中移到已實作，並明確標示 v1 僅支援 `names`。
- 更新 README 中的目前架構、檔案樹與 API 範例，反映 `EmailScanAgent`、`/api/pii/email-scan` 與相關 request / controller / test 檔案。
- 更新 README 的測試與手動驗證章節，使其涵蓋現有的 Email 掃描測試範圍、既有中文姓名偵測入口，以及目前已知限制。
- 不變更應用程式執行邏輯、API 契約或模型行為，只修正文檔內容。

## Capabilities

### New Capabilities
- `project-readme`: 定義專案 README 應如何準確反映目前已實作的能力、對外入口與驗證方式。

### Modified Capabilities
無。

## Impact

- Affected code: `README.md`
- API: 無 API 行為變更，僅補充與修正文檔中的既有端點說明
- Dependencies / systems: 無
- Testing: 以人工比對 README 與既有 routes、controllers、requests、agents、tests 的一致性為主
