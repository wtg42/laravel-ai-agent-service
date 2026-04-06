## ADDED Requirements

### Requirement: System prevents premature timeout for adaptive OCR API requests
The system SHALL ensure the `adaptive-ocr` API request can continue long enough to match the configured OCR provider timeout, so local long-running OCR analysis does not terminate early with an empty HTTP response.

#### Scenario: Request execution limit is raised for adaptive OCR
- **WHEN** the client submits a valid image to the `adaptive-ocr` API
- **THEN** the system raises the request execution limit for that API flow to at least cover the configured OCR provider timeout

#### Scenario: Slow OCR processing does not end with an empty response
- **WHEN** OCR analysis takes longer than the default local PHP request execution limit but still remains within the configured OCR timeout window
- **THEN** the system keeps the request alive and returns either a normal OCR response or a controlled application error response

#### Scenario: Other routes do not inherit the adaptive OCR execution change
- **WHEN** requests are sent to routes outside the `adaptive-ocr` API
- **THEN** the system does not broaden their execution limit as part of this change
