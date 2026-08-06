# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.1.0] - 2026-08-06

### Fixed
- `Service::send()` no longer throws `TypeError: Wearesho\Delivery\Result::__construct(): Argument #1
  ($messageId) must be of type string, null given` when TurboSMS rejects a recipient. The API returns
  `"message_id": null` for rejected recipients, and the previous `array_key_exists('message_id', ...)`
  guard accepted an explicit null
- `Response::CODE_SUCCESS_MESSAGE_ACCEPTED` corrected from `0` to `800` as documented by TurboSMS. The
  constant duplicated `CODE_SUCCESS` and `800` was absent from the success list, so a successfully queued
  message was reported as `Delivery\Result\Status::Failed`
- `response_status` and `response_code` are read with null-coalescing, removing undefined array key
  warnings when TurboSMS omits them

### Added
- Failure details are included in the `Delivery\Exception` message so they survive into logs without a
  dedicated exception class or handler: recipient, per-recipient status and code, and overall response
  status and code. `getCode()` returns the per-recipient code when available, otherwise the top-level code
- `Response::isSuccessCode()` for checking an arbitrary response code against the success list
- `Response::CODE_SUCCESS_PROCESSED` (`804`) and `Response::CODE_SUCCESS_PARTIAL_PROCESSED` (`805`), added
  to the TurboSMS code table on 2026-03-16, are now recognised as success codes. Neither is returned by
  the endpoints this library calls — they belong to the group endpoints — so this only affects
  `isSuccessCode()` when it is given an arbitrary code

### Changed
- `Service::batch()` yields `Failed` for a recipient TurboSMS rejected. Previously only the top-level
  response code was checked, so a recipient rejected inside a partially successful request (`802`/`803`)
  was yielded as `Accepted`, with the internal request key standing in for the `null` message id. A result
  is now `Accepted` only when the top-level **and** the per-recipient response codes are both success
  codes. `messageId()` still falls back to the request key, so a rejected recipient does not interrupt the
  iteration. Note this was already not the only way `batch()` can throw: an empty `response_result` for an
  individual request still raises mid-iteration and abandons the remaining results
- `Service::send()` applies the same per-recipient check when building a result. TurboSMS documents a
  rejected recipient as always carrying `message_id: null`, so a documented rejection raises the exception
  above and never reaches this check — it guards undocumented payloads only. The previous `send()`
  behaviour for a documented rejection was the `TypeError` described above, not an `Accepted` result
- The `Failed to get messageId (key not found)` exception is replaced by the detailed message above. It
  remains a `Delivery\Exception`, so existing catch blocks are unaffected

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
