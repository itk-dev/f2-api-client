# F2 API client

API client for [The cBrain F2 Platform](https://www.cbrain.com/software-pages/the-f2-platform).

## Installation

``` shell
composer require itk-dev/f2-api-client
```

## Testing

Edit `.env.local` and set these variables:

``` dotenv
# .env.local
F2_API_URI=
F2_API_USERNAME=
F2_API_SECRET=
F2_F2_USERNAME=
```

Run

``` shell
docker compose run --quiet --rm phpfpm composer install
docker compose run --quiet --rm phpfpm php bin/f2-api-client getServiceIndex
```

to check that you can talk to the F2 API.

Search cases with

``` shell
docker compose run --quiet --rm phpfpm php bin/f2-api-client searchCases '{"q": "test", "count": 10}'
```

## Development

For development (and testing) a couple of useful tasks are defined:

``` text
* f2-api-client:debug:       Debug bin/f2-api-client inside docker compose setup, e.g. `task f2-api-client:debug -- searchCases '{"q": "test", "count": 10}'`
* f2-api-client:run:         Run bin/f2-api-client inside docker compose setup, e.g. `task f2-api-client:run -- searchCases '{"q": "test", "count": 10}'`
```

---

``` mermaid
---
title: F2 Conceptual model
---
%% https://mermaid.ai/open-source/syntax/classDiagram.html
classDiagram
    Document --> Matter
    Matter --> CaseFile
    Party
    Note --> CaseFile: (only if not related to Matter)
    Note --> Matter: (only if not related to CaseFile)

    Chat --> Matter

    %% class Item{
    %%     +String title
    %% }
    %%
    %% Item <|-- Document
    %% Item <|-- Matter
    %% Item <|-- CaseFile
```

> The client MUST read the service index at runtime and use it to locate the links it needs.

<https://github.com/itk-dev/f2-api-client/blob/main/resources/f2-rest-docs-v13.pdf#page=8>
