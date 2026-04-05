## Purpose

Define the README expectations so project documentation stays aligned with implemented features, public API entry points, and the current codebase structure.

## Requirements

### Requirement: README 必須準確反映已實作能力
系統文件中的 `README.md` SHALL 準確區分目前已實作與尚未實作的能力，並不得將已存在於程式碼中的主要功能誤列為規劃中。

#### Scenario: 已實作的 Email 掃描能力被列為已完成
- **WHEN** repository 中已存在 `EmailScanAgent`、對應 controller、request、route 與 feature test
- **THEN** `README.md` 將 Email 掃描能力列在已實作區塊，且不再標示為規劃中能力

### Requirement: README 必須描述目前對外 API 入口
`README.md` SHALL 列出目前可用的對外 API 入口與其角色，讓整合方可從文件辨識現有端點用途。

#### Scenario: README 同時描述掃描入口與既有偵測入口
- **WHEN** `routes/api.php` 同時提供 `/api/pii/email-scan` 與 `/api/pii/chinese-names/detect`
- **THEN** `README.md` 會描述這兩個端點的用途，並標示 Email 掃描 v1 的輸出邊界

### Requirement: README 必須對齊目前程式結構與驗證方式
`README.md` SHALL 反映目前存在的 Agent、Controller、Request、測試檔案與測試方式，讓讀者可以從文件快速對照 codebase。

#### Scenario: README 檔案樹與測試說明對齊現況
- **WHEN** repository 中已存在 Email 掃描相關檔案與 feature tests
- **THEN** `README.md` 的架構說明、檔案樹與測試章節會包含這些已存在的檔案與測試範圍

### Requirement: README 不得承諾尚未實作的細節
`README.md` MUST 將未實作能力維持在高層規劃描述，且不得把尚未落地的辨識欄位、API 契約或工作流程寫成既有功能。

#### Scenario: Email 掃描 v1 只描述已支援的輸出
- **WHEN** `EmailScanAgent` 第一版僅回傳 `names`
- **THEN** `README.md` 只將姓名掃描描述為目前已支援內容，並把其他個資型別保留為後續規劃
