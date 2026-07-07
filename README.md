# F2 API client

API client for [The cBrain F2 Platform](https://www.cbrain.com/software-pages/the-f2-platform).

See the [F2 REST API documentation](resources/f2-rest-docs/f2-rest-docs-v13s.html#outline) for details.

## Installation

``` shell
composer require itk-dev/f2-api-client
```

## Usage

See [F2ApiClientCommand](src/Command/F2ApiClientCommand.php) for an example usage.

## Testing

Edit `.env.local` and set these variables:

``` dotenv
# .env.local
F2_API_URI=
F2_API_USERNAME=
F2_API_SECRET=
F2_F2_USERNAME=

F2_API_TIME_ZONE=
```

Run

``` shell
docker compose run --quiet --rm phpfpm composer install
docker compose run --quiet --rm phpfpm php bin/f2-api-client getServiceIndex
```

to check that you can talk to the F2 API.

> [!NOTE]
> We'll use `task f2-api-client:run` (cf. [Taskfile.yml](Taskfile.yml)) as a shorthand for running `docker compose run
> --quiet --rm phpfpm php bin/f2-api-client` (cf. [Development](#development)).
>
> Use `task f2-api-client:debug` to debug with Xdebug.

Search cases with

``` shell
task f2-api-client:run -- caseSearch test
```

See case details:

``` shell
task f2-api-client:run -- caseById 2204
```

## Development

For development (and testing) a couple of useful tasks are defined:

``` text
* f2-api-client:run:         Run bin/f2-api-client inside docker compose setup, e.g. `task f2-api-client:run -- caseSearch 'test'`
* f2-api-client:debug:       Debug bin/f2-api-client inside docker compose setup, e.g. `task f2-api-client:debug -- caseSearch 'test'`
```

### F2 REST documentation

The F2 REST documentation is available only as a PDF (!), but we need something we can refer to. Therefore, we convert
[the PDF](resources/f2-rest-docs/f2-rest-docs-v13.pdf) to HTML and serve that.

Run

``` shell
task f2-rest-docs:serve
```

and open <http://127.0.0.1:8787/>.

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

[F2 REST API, p. 4](resources/f2-rest-docs/f2-rest-docs-v13s.html#4)

---

> The client MUST read the service index at runtime and use it to locate the links it needs.

[F2 REST API, p. 8](./resources/f2-rest-docs/f2-rest-docs-v13s.html#8)
