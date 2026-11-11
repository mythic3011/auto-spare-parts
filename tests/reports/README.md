# Test Reports

This directory contains test execution reports.

## Files

- `testdox.html` - Human-readable test documentation in HTML format
- `testdox.txt` - Human-readable test documentation in text format
- `junit.xml` - JUnit format for CI/CD integration

## Viewing Reports

### HTML Report
Open `testdox.html` in your browser to see a formatted test report.

### Text Report
View `testdox.txt` for a simple text-based test report.

### CI/CD Integration
The `junit.xml` file can be used with CI/CD tools like Jenkins, GitLab CI, or GitHub Actions.

## Generating Reports

Reports are automatically generated when running PHPUnit:

```bash
vendor/bin/phpunit
```

## Coverage Reports

For code coverage reports, see the `coverage/` directory in the project root:

```bash
vendor/bin/phpunit --coverage-html coverage/
```

Then open `coverage/index.html` in your browser.
