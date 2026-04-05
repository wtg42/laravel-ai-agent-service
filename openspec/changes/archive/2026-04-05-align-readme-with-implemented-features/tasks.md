## 1. 盤點 README 與現況差異

- [x] 1.1 比對 `README.md` 與現有 routes、agents、controllers、requests、tests，列出所有不同步區塊
- [x] 1.2 確認 `EmailScanAgent` 的已實作邊界，特別是 `POST /api/pii/email-scan` 與 v1 僅支援 `names`

## 2. 更新 README 內容

- [x] 2.1 修正功能清單，將 Email 掃描移到已實作區塊，並收斂規劃中能力的描述
- [x] 2.2 更新目前架構、檔案樹與 API 範例，使其包含 Email 掃描入口與相關檔案
- [x] 2.3 更新測試方式、手動驗證範例與已知限制，反映現有 Email 掃描與中文姓名偵測能力

## 3. 驗證文件一致性

- [x] 3.1 重新檢查 `README.md` 是否與目前 codebase 及封存 OpenSpec change 一致
- [x] 3.2 確認 README 沒有把尚未實作的個資型別或流程誤寫成既有功能
