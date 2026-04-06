## 1. 開發入口與 supervisor script

- [x] 1.1 新增 `justfile`，提供以 `dev` 為主的本機 AI 開發入口
- [x] 1.2 新增 bash supervisor script，負責檢查 `ollama` 指令、啟動 `ollama serve` 與 `composer run dev`
- [x] 1.3 在 script 中實作任一側退出時清理另一側，以及 `SIGINT` / `SIGTERM` 的子程序收尾

## 2. 啟動失敗與責任邊界

- [x] 2.1 實作 `ollama` 缺失時的可讀錯誤訊息與非零退出
- [x] 2.2 實作 `ollama serve` 啟動失敗時的可讀錯誤訊息與整體流程退出
- [x] 2.3 確認 `composer run dev` 內容維持不變，且新的 `just dev` 只作為外層入口

## 3. 驗證與使用說明

- [x] 3.1 驗證正常啟動情境下 `just dev` 可同時帶起 Laravel 開發程序群與 Ollama
- [x] 3.2 驗證任一側異常退出時另一側會被終止，避免殘留長駐程序
- [x] 3.3 補充必要的使用說明，讓開發者知道何時應使用 `just dev`
