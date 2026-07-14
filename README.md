# Mean Kinetic Temperature (MKT)

A Symfony application for calculating the Mean Kinetic Temperature of a set of
time/temperature readings. MKT expresses a series of temperature fluctuations as
a single equivalent temperature, weighted towards the higher end of the range to
reflect the accelerated effect of heat. It is widely used in pharmaceutical
logistics to assess the impact of ambient temperature exposure on medicine
during storage and transport.

The application lets you upload a CSV of readings, give the dataset a name, and
stores it along with its calculated MKT. Datasets can be browsed, searched,
sorted, and inspected individually with a temperature graph.

## Tech stack

- **PHP 8.4** / **Symfony 8.1**
- **Doctrine ORM 3** with migrations, **MySQL 8.4**
- **Twig** + **Bootstrap 5** for the UI, **Chart.js** for the reading graph
- **PHPUnit** for tests
- **Docker** (php-fpm + nginx + MySQL) for a one-command run

## Running the application

The application ships as three containers — the PHP/Symfony app (php-fpm), an
nginx web server, and MySQL — orchestrated by `compose.yaml`.

```bash
docker compose -f compose.yaml up -d --build
```

The app is then available at:

```
http://localhost
```

On first start the app container waits for the database, runs the Doctrine
migrations, warms the Symfony cache, and then serves the application. No manual
setup steps are required — the schema is created automatically.

To follow the logs or stop the stack:

```bash
docker compose -f compose.yaml logs -f
docker compose -f compose.yaml down
```

To also remove the database volume:

```bash
docker compose -f compose.yaml down -v
```

### Configuration

The runtime configuration is defined directly in `compose.yaml`. The application
runs in `prod` mode and connects to the bundled MySQL service. The database
credentials and application secret can be overridden through environment
variables (`MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`, `APP_SECRET`) when needed.

## Using the application

**Upload a dataset.** From the upload page, give the dataset a name and choose a
CSV file. The parser accepts:

- A header row (`time,temperature`) in any column order, or a header-less file
  read positionally.
- Times as Unix timestamps or common date/time formats (`Y-m-d H:i:s`,
  `Y-m-d\TH:i:s`, and their minute-precision variants).
- Temperatures in degrees Celsius.

Sample files are provided in the `samples/` directory.

If a file cannot be parsed, every problem row is reported at once (with its line
number and reason) rather than stopping at the first error, so the whole file
can be corrected in a single pass.

**Browse datasets.** The index lists every dataset with its name, submission
time, and calculated MKT. The list can be sorted by name, submission time, or
MKT, searched by name, and is paginated.

**Inspect a dataset.** Opening a dataset shows its readings together with a graph
of temperature over time.

**Delete a dataset.** Datasets can be removed from the index; deletion is
CSRF-protected.

## How it works

The code is organised into focused, single-responsibility layers:

- **`src/Csv`** — parses an uploaded CSV into a list of `ParsedReading` value
  objects. It handles column detection, format normalisation, and validation,
  collecting all row-level errors into a single `CsvParseException`. The parser
  sits behind `TemperatureCsvParserInterface`, so an alternative format could be
  introduced without touching the rest of the application.
- **`src/Mkt`** — a pure calculator that turns a list of Celsius temperatures
  into the Mean Kinetic Temperature. It has no framework or persistence
  dependencies, which keeps the core formula isolated and directly unit-testable.
- **`src/Dataset`** — orchestrates an import: it drives the parser, builds the
  `Dataset` and its `Reading` entities, asks the calculator for the MKT, and
  persists the result.
- **`src/Entity` / `src/Repository`** — the Doctrine model. A `Dataset` owns many
  `Reading`s; `DatasetRepository` provides the searchable, sortable, paginated
  query used by the index.
- **`src/Controller`** — a thin HTTP layer that wires forms and templates to the
  services above.

### The MKT calculation

MKT is calculated with the Arrhenius-based formula using the assignment's
activation energy of **83.144 kJ/mol** and the gas constant **0.0083144
kJ/(mol·K)**:

1. Each Celsius reading is converted to Kelvin.
2. The mean of `exp(-Ea / (R·T))` is taken across all readings.
3. The result is converted back to a Celsius temperature.

Because MKT weights higher temperatures more heavily, the result sits above the
plain arithmetic mean whenever the readings vary — which is the entire point of
the metric.

## Design decisions

- **CSV as the input format.** CSV is the format temperature loggers export in
  practice, so it was chosen as the single, well-supported input. The parsing
  logic is kept behind an interface, leaving the door open to other formats.
- **Readings are treated as equally spaced in time.** MKT is calculated from the
  temperature values themselves. The recorded time of each reading is stored and
  used for display and ordering, and the calculation assumes uniform sampling
  intervals — the standard interpretation of MKT.
- **Defensive, forgiving parsing.** Real-world exports are messy, so the parser
  tolerates header/header-less files and mixed time formats, guards against
  implausible values, and reports every issue in a file at once.
- **A pure calculation core.** Keeping the MKT formula free of framework and
  database concerns makes it simple to reason about and to test in isolation.

## Testing

The test suite covers the two pieces of core logic — the CSV parser and the MKT
calculator — including known reference values, edge cases, and the full range of
validation and error-reporting behaviour.

```bash
docker compose -f compose.yaml exec app php bin/phpunit
```

## Project structure

```
src/
  Controller/   HTTP layer (index, upload, show, delete)
  Csv/          CSV parsing into ParsedReading value objects
  Dataset/      Import orchestration, upload form and DTO
  Entity/       Dataset and Reading Doctrine entities
  Mkt/          Mean Kinetic Temperature calculator
  Repository/   Dataset/Reading repositories (search, sort, pagination)
templates/      Twig views
migrations/     Doctrine migrations
samples/        Example CSV files
tests/          PHPUnit tests
docker/         Container entrypoint and nginx configuration
compose.yaml    App + nginx + MySQL
Dockerfile      PHP/Symfony image
nginx.Dockerfile  nginx image
```