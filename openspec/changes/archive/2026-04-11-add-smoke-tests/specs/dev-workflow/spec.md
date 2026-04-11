## ADDED Requirements

### Requirement: 系統提供以 just 為入口的冒煙測試指令
系統 SHALL 在 Justfile 提供 `smoke` recipe，讓開發者能以 `just smoke` 執行冒煙測試腳本。

#### Scenario: 執行 just smoke 觸發冒煙測試
- **WHEN** 開發者執行 `just smoke`
- **THEN** 系統執行 `node bin/smoke.js` 並將 exit code 傳遞給呼叫者
