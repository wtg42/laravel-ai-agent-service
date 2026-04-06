## 1. Agent 與決策契約

- [x] 1.1 建立 `AdaptiveOcrAgent`，定義圖片輸入場景的 instructions、provider、model 與 structured output schema
- [x] 1.2 定義 agent 決策欄位與允許動作，至少涵蓋 `rotate`、`sharpen`、`increase_contrast`、`crop`、`analyze_now`、`reject_image`
- [x] 1.3 定義 OCR 最終輸出結構，讓分析完成後能穩定回傳文字結果與必要狀態資訊

## 2. 圖片工具與處理輸出

- [x] 2.1 建立第一版影像工具：旋轉圖片、銳化圖片、提高對比、裁切圖片
- [x] 2.2 為每個工具定義一致的輸入參數與輸出資訊，至少包含處理後圖片位置與主要執行參數
- [x] 2.3 建立處理後圖片保存策略，讓每輪輸出都能與原圖及決策資訊建立關聯

## 3. 自適應 orchestration 流程

- [x] 3.1 建立應用層 orchestration loop，根據 agent 決策執行對應工具並重新附圖 prompt
- [x] 3.2 實作停止條件，限制最大工具步數、阻止無限制重複工具並支援 `reject_image` 結束流程
- [x] 3.3 在 agent 決定 `analyze_now` 後產出最終 OCR 結果，供下游文字型掃描流程重用

## 4. API 與驗證

- [x] 4.1 新增單張圖片輸入端點、request 驗證與 controller 入口
- [x] 4.2 串接圖片端點與自適應 OCR orchestration，回傳穩定的成功、不可分析與驗證錯誤結果
- [x] 4.3 確認既有純文字掃描流程不受新圖片辨識能力影響

## 5. 測試與可觀測性

- [x] 5.1 新增 feature tests，覆蓋合法圖片請求、非法請求與成功回傳 OCR 結果
- [x] 5.2 新增 agent / orchestration tests，覆蓋工具選擇、重複工具限制、最大步數停止與 `reject_image` 情境
- [x] 5.3 新增可觀測性驗證，確認每輪決策、工具參數、輸出圖片關聯與流程停止原因可被追蹤
