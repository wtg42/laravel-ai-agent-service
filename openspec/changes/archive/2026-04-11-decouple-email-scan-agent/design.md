## Context

`EmailScanAgent` 目前繼承 `ChineseNameDetectionAgent`，共用 `model()`、`timeout()`、`schema()` 三個方法。這個設計在升級 laravel/ai 到 v0.5 並改用 PHP attribute `#[Provider]` 時暴露出問題：PHP attributes 不會被子類別繼承，導致 `EmailScanAgent` 無聲地 fallback 到 OpenAI provider，造成 503 錯誤。

## Goals / Non-Goals

**Goals:**
- 將 `EmailScanAgent` 改為獨立 class，消除與 `ChineseNameDetectionAgent` 的繼承耦合
- 每個 agent 都是完整且自給自足的實作

**Non-Goals:**
- 不提取 trait 或共用抽象層（目前只有兩個 agent，尚未達到提取的臨界點）
- 不改變任何 API 行為、controller、或 Feature test

## Decisions

### 選擇完全獨立而非提取 Trait

共用程式碼只有約 18 行（`model()`、`timeout()`、`schema()`），提取 trait 會增加一個需要被理解的抽象層但帶不來對應的好處。等到第三個 agent 出現、同樣的程式碼出現三次時，再考慮提取。

### 重複 `schema()` 是可接受的

`schema()` 是 agent 的輸出契約，每個 agent 自己擁有完整的契約定義反而更清晰。未來若兩者的 schema 需要分歧，也不需要擔心影響對方。

## Risks / Trade-offs

- **少量程式碼重複**：`model()`、`timeout()`、`schema()` 在兩個 class 中各出現一次 → 接受，因為目前重複量小，且重複的是設定而非商業邏輯
- **未來同步成本**：若 Ollama 設定邏輯變複雜，需要在兩個地方更新 → 低風險，config 讀取邏輯本質上很穩定
