# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0] - 2026-01-12

### Added
- Support for separate Viber sender name configuration
  - New `ConfigInterface::getViberSenderName(): ?string` method
  - New optional constructor parameter in `Config` class: `$viberSenderName`
  - Environment variable support: `TURBOSMS_VIBER_SENDER`
  - Sender name priority: message-level > Viber-specific config > default config
  - Multi-channel support: different senders for SMS and Viber in same message

### Changed
- `Service::getRequestBody()` now applies Viber-specific sender when configured
- Viber sender name only used for Viber channel, SMS always uses default sender

### Breaking Changes
- Added required method `getViberSenderName()` to `ConfigInterface`

## [3.2.0] - 2026-01-12

### Added
- Viber message options supports IS_TRANSACTIONAL flag

## [3.1.0] - 2025-12-18

### Added
- Viber message options support via `ViberOptions` enum
  - `viberImageUrl`: Set image URL for Viber messages (maps to `image_url` API parameter)
  - `viberButtonText`: Set button caption for Viber messages (maps to `caption` API parameter)
  - `viberButtonUrl`: Set button action URL for Viber messages (maps to `action` API parameter)
- New `ViberOptions` enum class for mapping message options to Viber API parameters
- New `Service::mapViberRequestOptions()` protected method for processing Viber-specific options
- Comprehensive test coverage for Viber options functionality

### Changed
- Updated `Service::getRequestBody()` to include Viber options when channel is set to 'viber'
- Viber options are now properly isolated to the Viber channel and do not affect SMS channel requests

### Fixed
- Fixed issue where Viber options would replace instead of merge with base message structure
