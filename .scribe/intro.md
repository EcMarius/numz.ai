# Introduction

API for managing campaigns, leads, and sync operations in EvenLeads.

<aside>
    <strong>Base URL</strong>: <code>https://evenleads.com</code>
</aside>

    Welcome to the EvenLeads API documentation. This API allows you to programmatically manage your campaigns, leads, and sync operations.

    ## Getting Started

    To use this API, you'll need an API key. You can create one from your [API Settings](/settings/api) page.

    ## Authentication

    All API requests must include your API key in the **X-API-Key** header:

    ```
    X-API-Key: your_api_key_here
    ```

    Alternatively, you can pass it as a query parameter:

    ```
    ?api_key=your_api_key_here
    ```

    ## Base URL

    All API requests should be made to:
    ```
    https://evenleads.com/api/v1
    ```

    ## Rate Limiting

    - Manual sync operations are limited to once every 15 minutes per campaign
    - Standard rate limiting of 60 requests per minute applies to all endpoints

    ## Code Examples

    Throughout this documentation, you'll find code examples in multiple programming languages (Bash, JavaScript, PHP, Python). Use the language selector at the top to choose your preferred language.

