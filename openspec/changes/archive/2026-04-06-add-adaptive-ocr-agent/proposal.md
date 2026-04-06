## Why

目前系統只支援文字輸入的姓名與個資掃描，無法處理來自截圖、拍照文件或掃描圖檔的內容。既有固定式影像前處理流程也難以因應不同圖片品質問題，因此需要一個由 LLM 判斷是否要旋轉、銳化、裁切或直接分析的圖片辨識能力。

## What Changes

- 新增以單張圖片為輸入的 `AdaptiveOcrAgent` 能力，由模型先判斷圖片是否需要前處理，再決定何時開始內容辨識。
- 新增可由 agent 呼叫的影像處理工具，第一版聚焦於旋轉、銳化、提高對比與裁切等常見文件修正能力。
- 新增應用層 orchestration loop，負責執行 agent 決策、保存處理後圖片，並將新圖片重新附加給 agent 進行下一輪判斷。
- 新增 OCR 結果輸出，讓下游既有文字型掃描流程可以重用既有姓名與個資抽取邏輯。
- 第一版範圍限制為單張圖片與通用文字辨識，不包含多頁文件、PDF、專用證件辨識或模型微調。

## Capabilities

### New Capabilities
- `adaptive-ocr`: 以單張圖片為輸入，讓 agent 動態決定是否進行影像前處理並輸出可供下游分析的文字結果。

### Modified Capabilities

## Impact

- 影響 `app/Ai/Agents/` 中的 agent 設計，新增圖片決策型 agent。
- 影響 `app/Ai/Tools/` 中的工具邊界，新增影像轉換工具與其輸入輸出契約。
- 影響 API 輸入形式與驗證流程，需支援圖片上傳或圖片附件型請求。
- 影響應用層 orchestration 邏輯，需在 Laravel 端控制多輪 tool execution 與 re-prompt。
- 影響測試策略，需覆蓋 agent 決策、圖片工具呼叫、迴圈停止條件與 OCR 輸出穩定性。
