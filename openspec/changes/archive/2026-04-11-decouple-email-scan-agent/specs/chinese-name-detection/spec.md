## ADDED Requirements

### Requirement: ChineseNameDetectionAgent 不再被繼承
`ChineseNameDetectionAgent` SHALL 不作為其他 agent 的父類別，其實作為獨立 class 的完整設定。

#### Scenario: ChineseNameDetectionAgent 不具備被繼承的責任
- **WHEN** 新增 agent 需要偵測中文姓名
- **THEN** 該 agent 直接實作所需介面，而非繼承 `ChineseNameDetectionAgent`
