## Purpose

Define the expected behavior for detecting explicit Chinese personal full names from submitted text. Detailed implementation context is TBD.

## Requirements

### Requirement: System detects explicit Chinese full names from text
The system SHALL analyze a submitted text input and return explicit Chinese personal full names that are clearly mentioned in the text.

#### Scenario: Single explicit name is detected
- **WHEN** the client submits text containing one clearly stated Chinese full name
- **THEN** the system returns that name in the detection results

#### Scenario: Multiple explicit names are detected
- **WHEN** the client submits text containing multiple clearly stated Chinese full names
- **THEN** the system returns each detected name in the response results

### Requirement: System prioritizes precise person-name detection
The system SHALL prefer precision over recall and MUST exclude tokens that are not clearly identifiable as Chinese personal full names.

#### Scenario: Title-only reference is excluded
- **WHEN** the submitted text contains a title-based reference such as a surname with a generic honorific
- **THEN** the system does not return that token as a detected name

#### Scenario: Organization-like token is excluded
- **WHEN** the submitted text contains an organization, department, or location name that resembles a person name pattern
- **THEN** the system does not return that token as a detected name

### Requirement: System returns structured detection results
The system SHALL return name detection results in a stable JSON structure so downstream systems can consume them consistently.

#### Scenario: Detected result includes support information
- **WHEN** the system returns a detected Chinese name
- **THEN** the result includes the normalized name value and supporting evidence from the source text

#### Scenario: Duplicate names are normalized
- **WHEN** the same Chinese name appears multiple times in the submitted text
- **THEN** the system returns a normalized deduplicated result for that name

### Requirement: System handles empty and invalid requests predictably
The system SHALL validate incoming requests and MUST return a predictable response when no valid Chinese names are found.

#### Scenario: Empty content is rejected
- **WHEN** the client submits a request without usable text content
- **THEN** the system rejects the request with a validation error response

#### Scenario: No names are found
- **WHEN** the client submits valid text that contains no explicit Chinese full names
- **THEN** the system returns a successful response with an empty detection result set
