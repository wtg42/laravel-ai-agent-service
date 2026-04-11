## Why

目前專案的自動化測試全部使用 `Agent::fake()` mock 掉 AI 呼叫，無法驗證真實的 Ollama 整合是否正常運作。需要一個能在真實服務環境下快速確認所有 API 端點可正常回應的冒煙測試工具。

## What Changes

- 新增 `bin/smoke.js`：Node.js 冒煙測試腳本，對所有 PII API 端點發送真實 HTTP 請求並驗證回應結構
- 新增 `just smoke` 指令：透過 Justfile 執行冒煙測試
- 腳本在執行前先檢查 Ollama 與 Laravel 服務是否已啟動，任一服務未啟動則提示用戶並退出

## Capabilities

### New Capabilities

- `smoke-tests`：Node.js 冒煙測試腳本，檢查服務健康狀態並對全部 API 端點進行真實 HTTP 測試

### Modified Capabilities

- `dev-workflow`：Justfile 新增 `smoke` 指令入口

## Impact

- 新增檔案：`bin/smoke.js`
- 修改檔案：`justfile`（新增 `smoke` recipe）
- 執行環境依賴：Node.js（專案已有）、正在運行的 Ollama 與 Laravel 服務
