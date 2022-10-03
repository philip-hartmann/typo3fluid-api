# TYPO3 Fluid API

TYPO3 extension that adds an API to render TYPO3 Fluid templates and partials through HTTP/HTTPS requests.

## Requirements

The extension only works with an installed and configured TYPO3 instance.

## Installation

Install the extension with composer

```
composer req philip-hartmann/typo3fluid-api
```

Once installed edit the extension settings to enhance security.

## Usage

The API accepts JSON requests and returns the response as JSON as well. The API endpoint can be configured in the extension settings and is available by default under `/api/typo3fluid` of your TYPO3 installation.

### Request

```json
{
    "extension": "",
    "template": "",
    "partial": "",
    "section": "",
    "arguments": [],
    "password": ""
}
```

| field       | description                                                                   |
| ----------- | ----------------------------------------------------------------------------- |
| `extension` | snake case formatted extension name                                           |
| `template`  | relative template path name within a template root path, e.g. `Pages/Default` |
| `partial`   | relative partial path name within a partial root path, e.g. `Atomics/Button`  |
| `section`   | (optional) partial section name                                               |
| `arguments` | (optional) arguments that will be made available for the template or partial  |
| `password`  | password which is set in the extension settings                               |

Either use `template` or `partial`.

### Response

```json
{
    "data": "",
    "error": ""
}
```

| field   | description                       |
| ------- | --------------------------------- |
| `data`  | rendered fluid template html code |
| `error` | error message if available        |
