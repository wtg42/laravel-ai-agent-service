## Purpose

Define the local AI development workflow expectations so developers can start the Laravel development stack and Ollama runtime through a coordinated `just` entrypoint.

## Requirements

### Requirement: 系統提供以 just 為入口的本機 AI 開發工作流
系統 SHALL 提供一個以 `just` 為入口的本機開發指令，讓開發者能以單一命令啟動 Laravel 開發程序群與 Ollama runtime。

#### Scenario: 使用單一指令啟動 AI 開發工作流
- **WHEN** 開發者執行指定的 `just` 開發指令
- **THEN** 系統啟動 Laravel 開發程序群與 `ollama serve`

### Requirement: 系統在啟動前檢查 Ollama 指令可用性
系統 SHALL 在啟動 AI 開發工作流前檢查本機是否可執行 `ollama` 指令，且在缺少該指令時 MUST 以明確錯誤結束流程。

#### Scenario: 缺少 Ollama 指令時停止啟動
- **WHEN** 開發者執行 `just` 開發指令但系統找不到 `ollama`
- **THEN** 系統顯示可讀錯誤訊息並停止整個開發工作流

### Requirement: 系統以 supervisor script 協調程序生命週期
系統 SHALL 以專用 script 協調 `composer run dev` 與 `ollama serve` 的生命週期，而非僅依賴手動背景執行。

#### Scenario: 任一側異常結束時停止另一側
- **WHEN** `composer run dev` 或 `ollama serve` 任一方先結束
- **THEN** 系統停止另一方程序並結束整個開發工作流

#### Scenario: 開發者中斷指令時清理所有子程序
- **WHEN** 開發者以 `Ctrl+C` 或等效中斷方式停止 `just` 開發指令
- **THEN** 系統清理 `composer run dev` 與 `ollama serve` 的子程序

### Requirement: 系統保留 composer run dev 的既有責任邊界
系統 SHALL 保持 `composer run dev` 的既有行為不變，不直接將 `ollama serve` 納入 `composer.json` 的 `dev` script。

#### Scenario: 既有 composer dev 工作流仍可獨立使用
- **WHEN** 開發者直接執行 `composer run dev`
- **THEN** 系統維持既有 Laravel 開發程序群的啟動行為而不自動新增 Ollama runtime

### Requirement: 系統在啟動失敗時提供可讀錯誤回饋
系統 SHALL 在啟動 AI 開發工作流失敗時提供足以辨識原因的回饋，讓開發者能分辨是環境缺失或程序啟動失敗。

#### Scenario: Ollama 啟動失敗時回報原因
- **WHEN** `ollama serve` 無法成功啟動
- **THEN** 系統回報啟動失敗並結束整個開發工作流
