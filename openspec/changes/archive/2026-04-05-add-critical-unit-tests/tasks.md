## 1. 補強姓名正規化單元測試

- [x] 1.1 為 `NormalizeDetectedNames` 建立 Pest unit test 檔案與基本測試結構
- [x] 1.2 新增前綴稱謂、後綴職稱與空白清理案例，驗證有效姓名會被正規化保留
- [x] 1.3 新增職稱稱呼、部門名稱與機構尾碼案例，驗證非姓名值會被過濾
- [x] 1.4 新增重複姓名與不同 confidence 案例，驗證結果會去重並保留最高 confidence
- [x] 1.5 新增缺少 evidence、空 evidence、非數值 confidence 與越界 confidence 案例，驗證輸出穩定且 confidence 會被限制在合法範圍

## 2. 補強 agent 契約單元測試

- [x] 2.1 為 `ChineseNameDetectionAgent` 建立 unit tests，驗證 provider、model 與 timeout 設定行為
- [x] 2.2 為 `ChineseNameDetectionAgent` 新增 instructions 關鍵語意與 structured output schema 契約測試
- [x] 2.3 為 `EmailScanAgent` 建立 unit tests，驗證其 instructions 延伸自 email 掃描情境且維持 names 結構契約

## 3. 驗證與收斂

- [x] 3.1 執行新增 unit tests，確認與既有 feature tests 不衝突
- [x] 3.2 視需要整理重複或過度重疊的測試案例，維持測試層級責任清楚
