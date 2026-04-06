## Why

目前本機開發需要分別啟動 `composer run dev` 與 `ollama serve`，啟動順序、環境檢查與異常關閉都仰賴手動操作。這讓 AI 功能開發流程容易出現「Laravel 已跑、Ollama 沒跑」或其中一個服務異常結束後另一個仍殘留的狀況。

## What Changes

- 新增以 `just` 為入口的本機開發啟動方式，統一啟動 Laravel 開發程序群與 `ollama serve`。
- 新增 bash supervisor script，負責啟動前檢查 `ollama` 指令、協調 `composer run dev` 與 `ollama serve`，並在任一側異常結束時一併關閉另一側。
- 保持既有 `composer run dev` 行為不變，不將 `ollama serve` 直接納入 `composer.json` 的 `dev` script。
- 補充開發者可依賴的啟動契約與失敗行為，讓本機 AI 開發流程更一致。

## Capabilities

### New Capabilities
- `dev-workflow`: 提供以 `just` 加 supervisor script 啟動 Laravel 開發程序群與 Ollama 的一致本機開發工作流。

### Modified Capabilities

## Impact

- 影響本機開發入口，預計新增 `justfile` 與啟動 script。
- 影響開發者工作流程與失敗處理邏輯，但不直接改變應用 API。
- 影響本機 AI 依賴啟動方式，需明確定義 `ollama` 缺失、已在執行或異常退出時的處理。
- 不改動 `composer.json` 既有 `dev` script 的責任邊界。
