## Why

`EmailScanAgent` 目前透過繼承 `ChineseNameDetectionAgent` 來共用 `model()`、`timeout()`、`schema()` 三個方法，但繼承關係帶來了實際問題（PHP attributes 不繼承導致 provider 設定失效），且兩者並不具備真正的「是一種」關係。將兩個 agent 改為完全獨立的實作，消除這個脆弱的耦合。

## What Changes

- 將 `EmailScanAgent` 改為獨立 class，不再繼承 `ChineseNameDetectionAgent`
- `EmailScanAgent` 直接實作 `Agent`、`HasStructuredOutput`，使用 `Promptable` trait
- 複製必要的方法（`model()`、`timeout()`、`schema()`）到 `EmailScanAgent`
- 刪除 `EmailScanAgent` 上因繼承問題而需要重複宣告的 `#[Provider(Lab::Ollama)]`（現在兩個 class 各自有，本來就該如此）
- 更新 `EmailScanAgentTest`，補上對 `model()`、`timeout()`、`schema()` 的獨立驗證

## Capabilities

### New Capabilities

無新 capability。

### Modified Capabilities

- `email-scan`：實作細節變更（由繼承改為獨立實作），對外行為不變
- `chinese-name-detection`：實作細節變更（移除被繼承的角色），對外行為不變

## Impact

- 修改：`app/Ai/Agents/EmailScanAgent.php`
- 修改：`tests/Unit/Ai/Agents/EmailScanAgentTest.php`
- 不影響：API 端點、controller、HTTP 回應格式、任何 Feature test
