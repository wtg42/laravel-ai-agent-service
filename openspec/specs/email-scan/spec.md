## Purpose

Define the expected behavior for scanning submitted email text for explicit Chinese personal full names. Detailed implementation context is TBD.

## Requirements

### Requirement: System scans email text for explicit Chinese names
The system SHALL analyze submitted email text and return explicit Chinese personal full names that are clearly mentioned in the content.

#### Scenario: Single explicit name is detected from email text
- **WHEN** the client submits email text containing one clearly stated Chinese full name
- **THEN** the system returns that name in the `names` results

#### Scenario: Multiple explicit names are detected from email text
- **WHEN** the client submits email text containing multiple clearly stated Chinese full names
- **THEN** the system returns each detected name in the `names` results

### Requirement: System prioritizes precise name detection in email scans
The system SHALL prefer precision over recall and MUST exclude tokens in email text that are not clearly identifiable as Chinese personal full names.

#### Scenario: Title-only reference is excluded from email scan results
- **WHEN** the submitted email text contains a title-based reference such as a surname with a generic honorific
- **THEN** the system does not return that token in the `names` results

#### Scenario: Organization-like token is excluded from email scan results
- **WHEN** the submitted email text contains an organization, department, or location name that resembles a person name pattern
- **THEN** the system does not return that token in the `names` results

### Requirement: System returns a stable email scan structure
The system SHALL return email scan results in a stable JSON object and version one MUST expose supported detections through the `names` collection.

#### Scenario: Detected name includes support information
- **WHEN** the system returns a detected Chinese name from email text
- **THEN** the result includes the normalized name value and supporting evidence from the source text

#### Scenario: No explicit names are found in email text
- **WHEN** the client submits valid email text that contains no explicit Chinese full names
- **THEN** the system returns a successful response with an empty `names` result set

### Requirement: System validates unusable email scan requests predictably
The system SHALL validate incoming email scan requests and MUST reject requests without usable text content.

#### Scenario: Empty email content is rejected
- **WHEN** the client submits an email scan request without usable text content
- **THEN** the system rejects the request with a validation error response
