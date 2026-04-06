## Context

目前專案的本機開發入口是 `composer run dev`，它透過 `concurrently` 啟動 Laravel server、queue、logs 與 Vite。AI 功能則另外依賴 `ollama serve`，這造成開發者需要手動開兩組長駐服務，且無法保證啟動順序、環境檢查與異常退出時的一致行為。

本次變更不是在應用程式內新增功能，而是在本機開發工作流中新增一個可依賴的入口。主要問題不是「能不能同時啟動兩個指令」，而是如何在不改壞現有 `composer run dev` 責任邊界的前提下，提供一個能夠檢查 Ollama 是否存在、啟動兩側服務，並在任一側無預警結束時正確清理另一側的 supervisor 流程。

## Goals / Non-Goals

**Goals:**
- 提供 `just` 入口，讓開發者用單一指令啟動 Laravel 開發程序群與 `ollama serve`。
- 透過 bash script 實作最小可維護的 supervisor 行為，包括環境檢查、平行啟動、任一側退出時一併結束另一側，以及 `Ctrl+C` 清理。
- 保持 `composer run dev` 本身的語意不變，讓沒有使用 `just` 的情境仍可沿用原本 Laravel 開發指令。
- 提供清楚的失敗訊息，讓開發者能分辨是 `ollama` 未安裝、啟動失敗，還是其中一側中途異常結束。

**Non-Goals:**
- 不將 `ollama serve` 直接整合到 `composer.json` 的 `dev` script。
- 不建立通用的 process manager 或監控系統，僅處理本機開發所需的最小監控邏輯。
- 不在第一版處理多個 AI runtime、遠端 Ollama 或跨機器部署情境。
- 不保證自動偵測「Ollama 已在背景跑」並無縫接管，除非後續明確決定要支援該模式。

## Decisions

### 1. 使用 `just` 作為開發入口，但不讓 `just` 承擔監控邏輯

`just` 的角色應該只是開發者友善入口，而不是負責背景程序監控與訊號處理的主體。實際的程序協調邏輯應放在專用 bash script 中，由 `just` recipe 呼叫。這樣可以讓責任清楚分離：`just` 提供命令名稱，script 處理程序生命週期。

替代方案是直接把所有 `composer run dev & ollama serve & wait` 寫進 just recipe，但這會讓長駐程序、訊號處理與錯誤路徑都埋在單一 recipe 內，後續維護與除錯都較困難。

### 2. 保持 `composer run dev` 不變，將 Ollama 管理集中在 supervisor script

既有 `composer run dev` 已經承擔 Laravel 開發程序群的責任。此次變更不修改其內容，而是讓 supervisor script 在外層負責先檢查 `ollama` 指令，再平行啟動 `ollama serve` 與 `composer run dev`。這樣可以避免「啟動檢查在 script、實際啟動在 composer」的責任分散，也能保留既有 Laravel 工作流的通用性。

替代方案是把 `ollama serve` 直接加入 `composer.json` 的 `dev` script，但這會讓 Laravel 的標準開發入口硬性依賴本機 Ollama，且不利於未使用 AI 功能的開發情境。

### 3. 採用嚴格模式：若 `ollama` 不存在或無法啟動，整個 `just dev` 失敗

第一版應採取較簡單且可預期的嚴格模式。若系統找不到 `ollama` 指令，或 `ollama serve` 啟動失敗，script 應立即輸出可讀訊息並退出，不再繼續啟動 `composer run dev`。這符合目前專案已將本機 AI runtime 視為主要開發依賴的現況，也能避免開發者誤以為 OCR 流程可用。

替代方案是寬鬆模式，例如若缺少 Ollama 仍啟動 Laravel 開發程序群，但這會讓 `just dev` 的語意變得含糊，也會增加「看起來有啟動成功，實際 AI 功能不可用」的混亂。

### 4. supervisor script 需負責雙向清理與退出碼傳遞

script 需要做到以下行為：
- 啟動 `ollama serve` 與 `composer run dev`
- 等待任一方先結束
- 主動終止另一方
- 在 `SIGINT` / `SIGTERM` 時清理兩側程序
- 將導致結束的退出狀態回傳給呼叫端

這比單純背景執行更接近本機開發所需的最小 supervision。替代方案是只使用 shell 背景程序而不處理 `wait -n` 與 `trap`，但那樣容易留下殘留程序。

## Risks / Trade-offs

- [Ollama 已在背景執行時，嚴格模式可能造成二次啟動失敗] → 第一版先接受此限制，後續若實際使用造成摩擦，再考慮新增「已在執行則跳過」模式。
- [bash script 在不同 shell 環境中的相容性差異] → 將 script 明確標記為 bash，避免依賴較新的 shell 方言以外的行為。
- [外層 supervisor 與內層 concurrently 疊加，可能讓 log 輸出較複雜] → 保持外層只負責啟停，不在 script 內再加入額外多路輸出格式化。
- [開發者仍可能直接使用 `composer run dev` 而略過 Ollama] → 透過 just 入口與文件說明將 AI 開發的推薦流程明確化。

## Migration Plan

1. 新增 `justfile` 與 bash supervisor script。
2. 定義 `just dev` 的入口與錯誤訊息行為。
3. 驗證缺少 `ollama`、正常啟動、任一側退出與中斷清理等情境。
4. 補充必要文件或使用說明，讓開發者知道何時該用 `just dev`。

## Open Questions

- 是否要在第一版就支援「Ollama 已在背景執行時跳過 `ollama serve`」的容錯模式。
- `just` 指令名稱是否只保留 `dev`，還是同時提供 `dev-ai` / `dev-full` 類別名以區分用途。
