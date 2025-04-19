# Arffornia Website

Official website of Arffornia.


## How to Run in Dev mode

```bash
docker compose -f docker-compose-dev.yaml up --build -d
```

## 📘 API Documentation

This document describes the available API endpoints.

### 🧭 Endpoints Overview

| Method | Endpoint                                      | Description                                 | Parameters                  |
|--------|-----------------------------------------------|---------------------------------------------|-----------------------------|
| GET    | `/arffornia_v5/modlist`                       | Returns the list of mods as a JSON file. See [Nexus_Mods](<https://github.com/Arffornia/Nexus_Mods/tree/main/externalFiles>)      | -                           |
| GET    | `/best_player_vote/{size}`                    | Top players based on votes                  | `size` (integer)            |
| GET    | `/best_player_point/{size}`                   | Top players based on progress points                 | `size` (integer)            |
| GET    | `/profile/{playerName}`                       | Get a player’s profile by name              | `playerName` (string)       |
| GET    | `/profile/uuid/{playerUuid}`                  | Get a player’s profile by UUID              | `playerUuid` (UUID string)  |
| GET    | `/checkNewPlayer/{playerUuid}`                | Check if a player is new. Update the player name if it has changed.                    | `playerUuid` (UUID string)  |
| GET    | `/stages`                                     | Get all progression stages                              | -                           |
| GET    | `/stages/{playerUuid}`                        | Get a player’s progression stage            | `playerUuid` (UUID string)  |
| GET    | `/launcherVersionInfo/{dev?}`                 | [Deprecate] Get launcher version info                   | `dev` (optional, string)    |
| GET    | `/launcherImages`                             | Get launcher images                         | -                           |
| GET    | `/download/bootstrap`                         | [Deprecate] Download the bootstrap file                 | -                           |
| GET    | `/download/launcher`                          | Download the launcher executable            | -                           |
| GET    | `/shop/bestSallers/{size}`                    | Get best-selling shop items                 | `size` (integer), Range: [0;25]            |
| GET    | `/shop/newest/{size}`                         | Get newest shop items                       | `size` (integer), Range: [0;25]            |
| GET    | `/shop/sales/{size}`                          | Get items currently on sale                 | `size` (integer), Range: [0;25]            |


## 🧪 Tests


Tests are managed by **PHPUnit** 

You can run the tests using :

```bash
php artisan test
```