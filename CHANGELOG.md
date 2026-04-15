# Changelog

Wszystkie istotne zmiany w tym projekcie będą dokumentowane w tym pliku.

Format oparty na [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
a projekt stosuje [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.25.1] - 2026-04-15

### Added

- Ostrzeżenie gdy brak konfiguracji `<coverage><report><clover>` w phpunit.xml i używany jest tymczasowy plik dla raportu pokrycia kodu

## [1.25.0] - 2026-04-01

### Fixed

- Użycie osobnych flag `--group` i `--exclude-group` zamiast wartości rozdzielanych przecinkami

## [1.24.0] - 2026-04-01

### Fixed

- Przywrócenie acceptance do wymaganych zestawów testów (revert wcześniejszej zmiany)
- Naprawa niezgodności wersji w 1.24.0

## [1.23.0] - 2026-04-01

### Added

- Integracja z GitLab CI/CD ze współdzielonymi szablonami

### Fixed

- Rozwiązanie konfliktów PHPUnit z vendor w fixtures
- Dodanie jawnej zależności symfony/console dla CI
- Użycie `composer update` zamiast `install` w Dockerze

### Changed

- Wyrównanie z tmp-standards
- Aktualizacja README z aktualnymi funkcjonalnościami i konfiguracją

## [1.22.0] - 2026-03-11

### Added

- Opcja `--no-warmup` pozwalająca pominąć rozgrzewkę bazy danych dla testów jednostkowych

## [1.21.0] - 2026-03-09

### Added

- Równoległe uruchamianie testów z wykorzystaniem ParaTest (opcja `--parallel`)

## [1.2.0] - 2026-03-03

### Added

- Ulepszona weryfikacja konfiguracji PHPUnit (obsługa plików dist i nie-dist)
- Obsługa ścieżek pokrycia kodu

## [1.1.3] - 2026-03-01

### Changed

- Wyłączenie limitu pamięci dla procesu PHPUnit

## [1.1.2] - 2026-03-01

### Fixed

- Poprawki stabilności

## [1.1.1] - 2026-03-01

### Added

- Opcje `--group` i `--exclude-group` do filtrowania testów
- Dodanie `.coverage/` do `.gitignore`

## [1.1.0] - 2026-03-01

### Added

- Bundle Symfony, komendy i testy funkcjonalne z minimalną aplikacją Symfony
- Przykładowe agregatory testów

## [1.0.10] - 2025-12-18

### Added

- Narzędzia do pomiaru wydajności

## [1.0.9] - 2025-11-27

### Fixed

- Skrypt `run-if-modified.sh` powinien używać `sh` zamiast `bash`

## [1.0.8] - 2025-11-27

### Fixed

- Skrypt `run-if-modified.sh` powinien używać `sh` zamiast `bash`

## [1.0.7] - 2025-11-27

### Fixed

- Obsługa wyjątków w fazie arrange testów

## [1.0.6] - 2025-11-19

### Added

- Obsługa grup testów

## [1.0.5] - 2025-11-19

### Fixed

- Poprawka pobierania serwisu

## [1.0.4] - 2025-11-19

### Fixed

- Poprawka literówki w nazwie serwisu

## [1.0.3] - 2025-11-19

### Fixed

- Poprawki debugowania (dump)

## [1.0.1] - 2025-11-19

### Added

- Informacja o licencji MIT
- Obsługa niższych wersji bundle

## [1.0.0] - 2025-11-19

### Added

- Pierwsza wersja bundle do uruchamiania testów
