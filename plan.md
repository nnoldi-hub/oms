# OMS - Plan profesional de dezvoltare

## Obiectiv

OMS (Organizare Mese Santier) este o aplicatie Laravel pentru planificarea meselor de lucru, coordonarea voluntarilor, gestionarea alergiilor si calcularea necesarului de cumparaturi. Administrarea se face prin Filament, iar familiile preparatoare primesc o pagina publica pentru fiecare zi.

**MVP:** administratorul poate planifica 16 saptamani, echipele completeaza datele autorizate, iar familiile consulta de pe mobil meniul, instructiunile si cantitatile calculate.

## Roluri si limite de acces

| Rol | Permisiuni |
| --- | --- |
| Administrator | Administrare completa: utilizatori, congregatii, saptamani, meniuri, zile si voluntari. |
| Coordonator congregatie | Consulta doar propria congregatie; gestioneaza voluntarii zilelor alocate. |
| Echipa constructii | Modifica exclusiv `estimated_people` pentru zilele disponibile. |
| Echipa gastronomica | Gestioneaza meniurile, ingredientele si instructiunile. |
| Familie preparatoare | Consulta prin link public datele nepersonale ale zilei repartizate. |

Autorizarea se implementeaza cu Laravel Policies si scope-uri Eloquent. Restrictiile sunt validate pe server, nu doar ascunse in Filament.

## Arhitectura si model de date

- PHP 8.3+, Laravel 12, MySQL/MariaDB in productie si Filament v4.
- Fus orar `Europe/Bucharest`; interfata in romana.
- `users`: date autentificare, `role`, `congregation_id` nullable pentru administrator.
- `congregations`: denumirea grupurilor participante.
- `weeks`: `week_number`, `start_date`, `congregation_id`; numarul saptamanii este unic.
- `menus`: `name`, `instructions`, `ingredients` JSON si `packaging_cost`.
- `daily_meals`: `meal_date`, `week_id`, `congregation_id`, `menu_id`, `estimated_people`, `notes`, `public_token`, status de publicare; o singura zi pentru fiecare data. Congregatia de pe zi permite impartirea unei saptamani intre echipe.
- `volunteers`: date de contact, rol, alergii si `daily_meal_id`.
- Relatii: congregatia are saptamani/utilizatori; saptamana are zile; ziua apartine saptamanii si meniului si are voluntari.

Pentru MVP, `ingredients` ramane JSON cu `{name, quantity_per_person, unit}`. Normalizarea intr-un tabel separat se reanalizeaza cand apar stocuri, preturi per produs sau rapoarte avansate. Fiecare link public foloseste un `public_token` aleator, neghicibil si nu afiseaza telefoane, alergii sau voluntari.

## Milestone-uri

Statusul fiecarui rezultat se actualizeaza in [progress.md](progress.md).

### M1 - Fundatie tehnica (2-3 zile)

**Livrabile:** proiect Laravel, configurare `.env` si MySQL local, Filament, cont administrativ, migrari, modele Eloquent, factory-uri de baza.

**Acceptanta:** `php artisan migrate:fresh` ruleaza fara erori; relatiile sunt verificate; nu pot exista doua zile pentru aceeasi data si o alergie fara detalii.

### M2 - Acces si autorizare (2 zile)

**Livrabile:** roluri, politici pentru resursele Filament, scope-uri pentru congregatie si formulare blocabile per rol.

**Acceptanta:** Coordonatorul Congregației 2 nu poate vedea, edita sau accesa prin URL datele Congregației 1; echipa de constructii poate salva doar `estimated_people`; administratorul are acces complet.

### M3 - Meniuri si alergii (2-3 zile)

**Livrabile:** resource Filament pentru meniuri cu Repeater de ingrediente, validari de cantitate/unitate, formular voluntari si alerta de alergie.

**Acceptanta:** un meniu se poate reutiliza in zile diferite; alerta apare corect; detaliile alergiilor nu apar in pagina publica.

### M4 - Calcul, planificare si publicare (3 zile)

**Livrabile:** serviciul `MealRequirementCalculator`, calcul pentru ingrediente/caserole/cost ambalaje si pagina publica protejata de token.

**Acceptanta:** schimbarea numarului de la 30 la 50 recalculeaza corect toate cantitatile; linkul afiseaza doar zile publicate; calculatorul are teste pentru ingrediente multiple si cantitati fractionare.

### M5 - Date initiale si calitate (2-3 zile)

**Livrabile:** seeder pentru 16 saptamani si 80 zile incepand cu 28 noiembrie 2026, date pilot si teste feature pentru acces, calcul si link public.

**Acceptanta:** seeder-ul produce distributia aprobata; `php artisan test` ruleaza fara erori pe baza de test; scenariile de acces neautorizat sunt acoperite automat.

### M6 - Lansare si operare (1-2 zile)

**Livrabile:** configuratie productie, HTTPS, backup, logging, checklist de lansare si instructaj de 15 minute pentru fiecare rol.

**Acceptanta:** backup-ul a fost testat prin restaurare; datele personale nu sunt expuse; fiecare rol poate finaliza propriul flux de lucru de pe telefon.

## Backlog prioritar

1. Confirmarea regulii exacte pentru impartirea ultimei saptamani intre cele trei congregatii.
2. Initializarea Laravel si Filament in Laragon.
3. Migrari, modele si teste de integritate pentru M1.
4. Politici si filtrare obligatorie pentru M2.
5. Formular de meniuri si avertizari de alergie.
6. Calculator, pagina publica si teste.
7. Seeder, date pilot, exercitiu de lansare.

## Calitate si securitate

- Orice operatie de creare, citire, modificare sau stergere valideaza rolul si congregatia pe server.
- Telefoanele si alergiile sunt date personale, accesibile numai celor care au nevoie operationala de ele si niciodata public.
- Calculatorul este un serviciu testat, fara logica duplicata intre Filament si Blade.
- O etapa se inchide doar cand livrabilele, criteriile de acceptanta, testele relevante si actualizarea jurnalului sunt finalizate.



