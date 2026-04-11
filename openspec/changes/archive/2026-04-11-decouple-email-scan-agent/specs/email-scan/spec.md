## ADDED Requirements

### Requirement: EmailScanAgent 為獨立實作，不依賴繼承
`EmailScanAgent` SHALL 直接實作 `Agent` 與 `HasStructuredOutput` 介面，並使用 `Promptable` trait，不繼承任何其他 agent class。

#### Scenario: EmailScanAgent 獨立持有完整設定
- **WHEN** `EmailScanAgent` 被實例化
- **THEN** 該 class 自身包含 provider、model、timeout 及 schema 的完整定義，不依賴父類別
