# OMS - Jurnal de progres

## Reguli de actualizare

- Actualizeaza documentul la finalul fiecarei sesiuni de dezvoltare.
- Foloseste `Neinceput`, `In lucru`, `Blocat` sau `Finalizat`.
- Marcheaza o etapa finalizata numai dupa verificarea criteriilor si testelor din [plan.md](plan.md).
- Noteaza orice decizie sau blocaj care schimba domeniul proiectului.

## Panou de progres

| Milestone | Status | Progres | Ultima actualizare | Urmatorul rezultat verificabil |
| --- | --- | --- | --- | --- |
| M1 - Fundatie tehnica | In lucru | 75% | 30.08.2026 | Cont administrativ real si configurare MySQL Laragon. |
| M2 - Acces si autorizare | Finalizat | 100% | 30.08.2026 | Verificare manuala a rolurilor pe conturile reale. |
| M3 - Meniuri si alergii | In lucru | 95% | 30.08.2026 | Verificare manuala a fluxurilor Filament cu utilizatori reali. |
| M4 - Calcul si publicare | In lucru | 90% | 30.08.2026 | Verificare manuala cu o zi publicata din panel. |
| M5 - Seeding si calitate | In lucru | 90% | 30.08.2026 | Introducerea conturilor reale si verificarea manuala a fluxurilor. |
| M6 - Lansare si operare | Neinceput | 0% | - | Checklist productie si test operational final. |

## Activitate

### 30.08.2026 - Planificare initiala

- Status: Finalizat
- Realizat: domeniul MVP, rolurile, modelul de date, milestone-urile si criteriile de acceptanta sunt documentate in [plan.md](plan.md).
- Verificat: workspace-ul contine momentan doar documentatia; proiectul Laravel nu este inca initializat.
- Urmatorul pas: confirmarea regulii de repartizare a saptamanilor, apoi initializarea M1.

### 30.08.2026 - Initializare M1

- Status: In lucru
- Realizat: au fost confirmate executabilele PHP 8.3.30 si Composer 2.9.4 din Laragon; Laravel 12.68.0 si Filament 4.12.6 sunt instalate, iar panelul administrativ este disponibil la `/admin`.
- Realizat: au fost implementate migrarile, modelele, relatiile, factory-urile si regulile de integritate pentru cele sase entitati OMS.
- Verificat: `php artisan migrate:fresh` ruleaza fara erori; testul de model OMS are 3 scenarii si 7 asertiuni verzi.
- Decizie: Laravel 12 inlocuieste Laravel 11, deoarece Composer blocheaza versiunile Laravel 11 disponibile din cauza alertelor de securitate.
- Decizie: Filament 4 inlocuieste Filament 3, deoarece Composer blocheaza versiunea Filament 3 disponibila din cauza unei alerte de securitate.
- Urmatorul pas: crearea contului administrativ real, apoi inceperea M2 - acces si autorizare.

### 30.08.2026 - Acces si autorizare M2

- Status: In lucru
- Realizat: politici pentru congregatii, saptamani, zile de masa si voluntari; resource-uri Filament pentru aceste entitati.
- Realizat: filtre de congregatie in listele Filament si in selectorul de zi pentru voluntari.
- Realizat: echipa de constructii poate modifica numai `estimated_people`; interfata blocheaza celelalte campuri, iar modelul respinge orice salvare directa nepermisa.
- Verificat: suita de autorizare are 4 scenarii si 13 asertiuni verzi, inclusiv izolarea datelor intre doua congregatii.
- Urmatorul pas: resource administrativ pentru utilizatori si crearea conturilor operationale, apoi M3 - meniuri si alergii.

### 30.08.2026 - Administrare utilizatori M2

- Realizat: resource Filament pentru utilizatori, accesibil exclusiv administratorului.
- Realizat: rolurile sunt selectate dintr-o lista controlata, congregatia este obligatorie pentru rolurile operationale, iar o parola goala nu inlocuieste parola existenta la editare.
- Securitate: administratorul nu isi poate sterge propriul cont; coordonatorii nu pot consulta sau administra conturi.
- Verificat: suita de autorizare are 5 scenarii si 16 asertiuni verzi.
- Urmatorul pas: crearea conturilor operationale reale; aceasta necesita parolele alese de administrator.

### 30.08.2026 - Cont administrativ initial

- Status: Finalizat
- Realizat: primul cont administrativ OMS a fost creat prin generatorul Filament pentru panelul `admin`.
- Verificat: generatorul a confirmat activarea accesului la `/admin/login`; suita completa de regresie trece cu 16 teste si 45 asertiuni.
- Urmatorul pas: administratorul se autentifica si creeaza conturile reale pentru coordonatori, constructii si echipa gastronomica din resource-ul Utilizatori.

### 30.08.2026 - Interfata OMS in romana

- Status: Finalizat
- Realizat: aplicatia foloseste numele OMS, fusul orar `Europe/Bucharest` si localizarea `ro`.
- Realizat: navigatia, formularele, tabelele si paginile de detalii ale modulelor OMS folosesc etichete in romana fara diacritice.
- Realizat: traducerile Filament sunt publicate local si normalizate fara diacritice pentru login, actiuni, notificari si controale standard.
- Design: panoul foloseste o paleta verde-teal, pictograme specifice modulelor si grupuri de navigatie `Planificare`, `Bucatarie` si `Administrare`.
- Verificat: pagina `/admin/login` a fost verificata vizual dupa golirea cache-urilor; brandingul OMS si textele romanesti fara diacritice sunt afisate corect.

### 30.08.2026 - Redesign dashboard OMS

- Status: Finalizat
- Realizat: paleta panelului foloseste smarald pentru actiuni primare, zinc pentru tonurile neutre, amber pentru avertismente si rose pentru erori.
- Realizat: comutatorul light/dark este activ; utilizatorul poate alege tema din partea de sus a panelului.
- Realizat: widget-urile implicite au fost inlocuite de indicatori pentru persoane estimate azi, alergii declarate in saptamana curenta si meniuri active.
- Realizat: resursa Voluntari afiseaza o insigna cu numarul de voluntari din saptamana curenta.
- Verificat: widget-ul de statistici are test automat; cardul pentru persoane afiseaza corect valoarea zilei curente.

### 30.08.2026 - Rapoarte operationale

- Status: Finalizat
- Realizat: raport saptamanal imprimabil cu lista de cumparaturi bruta, numar total de caserole, cost estimat, programari si lista de alergii.
- Realizat: raportul agregă ingredientele tuturor zilelor din saptamana si semnaleaza zilele fara meniu, pentru a nu produce totaluri incomplete fara avertizare.
- Realizat: exporturi native CSV/XLSX pentru zilele de masa si voluntari, cu filtre pentru saptamana si alergii in lista de voluntari.
- Securitate: raportul saptamanal este disponibil numai dupa autentificare si aplica politica saptamanii; exporturile pornesc din interogarile Filament deja filtrate pe congregatie.
- Operare: exporturile native ruleaza prin coada Laravel. Pentru exporturi locale, porneste `php artisan queue:work` intr-un terminal separat daca nu folosesti scriptul `composer dev`.
- Verificat: testele rapoartelor acopera agregarea cantitatilor si blocarea unui coordonator de la raportul altei congregatii; suita completa are 19 teste si 56 asertiuni verzi.

### 30.08.2026 - Catalog 12 meniuri si ghid echipe

- Status: Finalizat
- Realizat: cele 12 meniuri standard au inlocuit meniurile pilot in seeder si in baza locala, cu ingrediente brute per portie in `kg` sau `buc`.
- Realizat: rotatia de 80 zile foloseste catalogul de 12 meniuri, iar rerularea seeder-ului nu creeaza meniuri sau zile duplicate.
- Verificat: Meniul 10 calculeaza pentru 50 persoane exact 4.5 kg fasole boabe uscata, 4 kg ciolan afumat dezosat si 5 kg gogonele.
- Realizat: ghidul operational pentru constructii, coordonatori, bucatarie si distributie este disponibil in `docs/ghid-logistic-mese-santier.md`.
- Urmatorul pas: introducerea conturilor reale si verificarea manuala a fluxurilor Filament cu echipele.

### 30.08.2026 - Alegerea retetelor si ciorba saptamanala

- Status: Finalizat
- Realizat: fiecare congregatie are o lista proprie de `Retete aprobate pentru congregatie`; coordonatorul poate restrange sau extinde lista doar pentru congregatia sa.
- Realizat: in `Zile de masa`, coordonatorul poate alege numai feluri principale si ciorbe aprobate de congregatia responsabila.
- Realizat: `Ciorba de legume` este configurata ca reteta separata si programata suplimentar o data pe saptamana, in prima zi. Sistemul nu permite a doua ciorba in aceeasi saptamana.
- Realizat: aceeasi reteta principala nu poate fi aleasa la mai putin de sase zile calendaristice. Astfel, o reteta pregatita vineri poate reaparea cel mai devreme joia urmatoare.
- Realizat: ingredientele ciorbei sunt incluse in lista saptamanala de cumparaturi, iar ciorba este afisata in raportul saptamanal si pe pagina publica a zilei.
- Verificat: testele acopera intervalul de sase zile, limita de o ciorba saptamanala, selectiile implicite si compatibilitatea rapoartelor; suita completa are 23 teste si 63 asertiuni verzi.

### 30.08.2026 - Simplificare flux operational

- Status: Finalizat
- Decizie: planificarea operationala se face fara nume de persoane. Unitatea de lucru este `zi de masa`: congregatie responsabila, fel principal, ciorba optionala si numar estimat de portii.
- Realizat: `Voluntari` este ascuns din navigatia principala, iar dashboard-ul afiseaza zile planificate in locul informatiilor despre voluntari sau alergii.
- Realizat: raportul saptamanal include numai lista de cumparaturi si planificarea pe zile: data, congregatie, fel principal, ciorba si portii estimate.
- Realizat: ghidul operational a fost simplificat pentru planificare, export CSV/XLSX, tiparire PDF si trimiterea raportului catre congregatie.
- Verificat: testele de raport confirma ca datele personale nu sunt afisate; dashboard-ul si raportul simplificat sunt testate automat.

### 30.08.2026 - Calendar saptamanal si varietate ciorbe

- Status: Finalizat
- Realizat: pagina `Calendar saptamanal` permite alegerea unei saptamani si afiseaza cartonas pentru fiecare zi, cu data, congregatia, felul principal, ciorba si portiile estimate.
- Realizat: calendarul contine un buton direct catre raportul saptamanal imprimabil.
- Realizat: catalogul are acum patru ciorbe selectabile: Ciorba de legume, Ciorba de perisoare, Ciorba a la grec si Supa cu galuste.
- Decizie: Ciorba de legume ramane aleasa automat in datele pilot. Celelalte trei ciorbe au gramaje initiale per portie si trebuie confirmate de echipa de bucatarie inainte de folosirea pentru cumparaturi reale.
- Verificat: pagina calendarului este acoperita de test automat, baza locala este actualizata, iar suita completa are 24 teste si 70 asertiuni verzi.

### 30.08.2026 - Actualizare rapida portii din calendar

- Status: Finalizat
- Realizat: calendarul saptamanal foloseste grid cu 3 coloane pe desktop, 2 coloane pe tableta si un card pe mobil.
- Realizat: fiecare cartonas separa vizual congregatia, felul principal, ciorba suplimentara si portiile estimate.
- Realizat: administratorul si echipa de constructii pot actualiza numarul de portii direct in cartonas si salva cu butonul bifat, fara reincarcarea paginii.
- Securitate: salvarea verifica permisiunea zilei si accepta numai numere intre 0 si 5000; coordonatorii au acces de citire la calendar.
- Verificat: testul Livewire confirma salvarea asincrona a portiilor din calendar.
- Verificat: gridul calendarului afiseaza 3 coloane pe desktop, 2 pe tableta si o coloana pe mobil; suita completa are 25 teste si 72 asertiuni verzi.

### 30.08.2026 - Corectie afisare cartonase calendar

- Status: Finalizat
- Problema: clasele de layout ale calendarului nu erau incluse in tema Filament compilata, astfel zilele se afisau vertical si fara aspect de cartonas.
- Realizat: calendarul foloseste acum CSS local scoped, cu 3 coloane reale pe ecrane mari, 2 pe tableta si 1 pe mobil.
- Realizat: campul de portii si butonul bifat folosesc controale Livewire directe, astfel valoarea introdusa este trimisa si salvata la apasare fara reincarcarea paginii.
- Verificat: testul calendarului confirma randarea si salvarea rapida a portiilor.

### 30.08.2026 - Estimare cost ingrediente

- Status: Finalizat
- Realizat: fiecare ingredient din formularul de meniu are campul optional `Pret estimat / unitate`, exprimat in RON pentru `kg`, `l` sau `buc`.
- Realizat: calculatorul determina costul estimat pe ingredient, subtotalul ingredientelor, costul ambalajelor si totalul estimat al fiecarei zile.
- Realizat: raportul saptamanal afiseaza pretul si costul estimat pe ingredient, plus un tabel separat cu costul ingredientelor, ambalajelor si totalul pentru fiecare zi.
- Calitate: cand un ingredient nu are pret, raportul afiseaza `De configurat` si marcheaza totalul ca partial, fara a afisa o estimare financiara inselatoare.
- Verificat: testele acopera calculul complet al costurilor si cazul cu pret lipsa.

### 30.08.2026 - Corectie raport cost pentru zero portii

- Status: Finalizat
- Problema: o zi cu 0 portii si un pret configurat producea `DivisionByZeroError` la calculul pretului mediu pe unitate.
- Realizat: calculatorul trateaza cantitatea zero ca o cerinta valida cu cost total 0 RON, fara impartire la zero.
- Verificat: test automat pentru masa cu 0 portii si pret configurat.

### 30.08.2026 - Corectie agregare saptamanala pentru zero portii

- Status: Finalizat
- Problema: raportul saptamanal putea produce `DivisionByZeroError` cand combina acelasi ingredient din mai multe zile cu 0 portii.
- Realizat: agregarea pastreaza pretul unitar configurat, cantitatea 0 si costul 0 RON fara impartire la zero.
- Verificat: test automat pentru doua zile cu 0 portii si acelasi ingredient.

### 30.08.2026 - Asistent congregatie si fise personalizate

- Status: Finalizat
- Realizat: congregatiile au campuri optionale pentru nume, telefon si email ale asistentului responsabil de organizarea meselor.
- Realizat: Calendarul saptamanal ofera cate un buton `Fisa [congregatie]` pentru fiecare congregatie programata in saptamana aleasa.
- Realizat: fisa personalizata include numai zilele, portiile, preparatele, cumparaturile si costurile congregatiei respective, impreuna cu datele asistentului.
- Securitate: administratorul poate genera orice fisa, iar un coordonator poate genera numai fisa congregatiei proprii.
- Verificat: test automat confirma ca o fisa nu afiseaza meniurile altei congregatii si include corect datele asistentului.

### 30.08.2026 - Ghid de igiena si alergeni in fisa

- Status: Finalizat
- Realizat: fiecare reteta are lista controlata de alergeni declarati, configurabila din formularul Meniuri.
- Realizat: fisa personalizata a congregatiei afiseaza alergenii pentru felul principal si ciorba, plus un ghid imprimabil de igiena, prevenire a contaminarii incrucisate si comunicare cu persoanele alergice.
- Calitate: retetele fara alergeni confirmati sunt marcate `De confirmat`; nu sunt prezentate ca fiind fara risc fara verificarea ingredientelor.
- Realizat: au fost introduse declaratiile cunoscute pentru retetele cu gluten, oua, lapte si telina; catalogul local a fost actualizat prin seeder.
- Verificat: testele acopera validarea alergenilor si continutul de siguranta din fisa congregatiei.

### 30.08.2026 - Publicare GitHub si pregatire Hostico

- Status: Finalizat
- Realizat: proiectul OMS este publicat in repository-ul GitHub `https://github.com/nnoldi-hub/oms` pe ramura `main`.
- Securitate: `.env`, baza SQLite locala, `vendor` si alte fisiere locale sensibile sunt excluse prin `.gitignore`.
- Realizat: `.env.example` si README contin configuratia de productie MySQL, domeniul `modulia.app` si pasii de deploy Hostico/cPanel.
- Urmatorul pas: configurarea bazei MySQL, Document Root catre `public` si a fisierului `.env` in contul Hostico.

### 30.08.2026 - Corectie izolare baza de test

- Status: Finalizat
- Problema: testele PHPUnit foloseau baza SQLite locala a aplicatiei, iar testele cu `RefreshDatabase` puteau elimina conturi si date introduse manual.
- Realizat: `phpunit.xml` foloseste acum SQLite in memorie exclusiv pentru mediul de test.
- Verificat: suita completa ruleaza cu 16 teste si 45 asertiuni, fara a modifica baza locala.
- Urmatorul pas: recrearea administratorului local o singura data; rularea ulterioara a testelor nu ii va mai afecta contul.

### 30.08.2026 - Meniuri si alergii M3

- Status: In lucru
- Realizat: resource Filament pentru meniuri, cu Repeater pentru ingrediente per persoana, cantitati pozitive si unitati standardizate (`kg`, `g`, `l`, `buc`).
- Realizat: validarea ingredientelor este aplicata si in model, nu doar in formular; echipa gastronomica poate administra meniuri, iar coordonatorii nu au acest drept.
- Realizat: zilele de masa afiseaza un indicator rosu daca exista voluntari cu alergii; formularul cere detaliile alergiei numai cand alergia este declarata.
- Verificat: testele pentru meniuri acopera autorizarea echipei gastronomice si respingerea ingredientelor invalide.
- Urmatorul pas: validarea manuala a formularului Filament cu un cont operational, apoi M4 - calculatorul de necesar si pagina publica.

### 30.08.2026 - Calculator si publicare M4

- Status: In lucru
- Realizat: serviciul `MealRequirementCalculator` calculeaza si grupeaza ingredientele dupa denumire si unitate, pe baza numarului estimat de persoane.
- Realizat: calculatorul calculeaza si numarul de caserole, precum si costul total al ambalajelor.
- Realizat: fiecare zi primeste automat un token UUID; ruta publica afiseaza numai zilele publicate, cu meniu, instructiuni si lista de cumparaturi optimizata pentru mobil.
- Securitate: pagina publica nu incarca voluntari, telefoane sau informatii despre alergii; zilele in ciorna raspund cu `404` chiar daca tokenul este cunoscut.
- Verificat: 3 teste si 10 asertiuni acopera calculul, publicarea si protejarea datelor personale.
- Urmatorul pas: publicarea manuala a unei zile pilot din panel, apoi M5 - date initiale si testare.

### 30.08.2026 - Seeding si calitate M5

- Status: In lucru
- Realizat: fiecare zi are congregatia responsabila proprie, astfel incat accesul si repartizarea nu depind exclusiv de congregatia saptamanii.
- Realizat: seeder-ul idempotent creeaza 3 congregatii, 3 meniuri pilot, 16 saptamani si 80 zile incepand cu 28.11.2026.
- Realizat: saptamana 16 este impartita intre congregatii in ordinea 1, 1, 2, 2, 3.
- Verificat: testul seeder-ului confirma numarul de date, data de start, impartirea finala si lipsa dublarilor la a doua rulare; baza locala a fost populata cu `php artisan db:seed`.
- Urmatorul pas: conturile operationale si meniurile reale, apoi verificarea manuala a fluxurilor din panel si pagina publica.

## Decizii si blocaje

| Data | Tip | Detaliu | Impact |
| --- | --- | --- | --- |
| 30.08.2026 | Decizie | Ingredientele raman JSON in MVP. | Dezvoltare rapida; normalizarea se reevalueaza pentru stocuri sau preturi per ingredient. |
| 30.08.2026 | Decizie | Se foloseste Laravel 12 in loc de Laravel 11. | Versiunile Laravel 11 disponibile sunt blocate de Composer prin alerte de securitate. |
| 30.08.2026 | Decizie | Se foloseste Filament 4 in loc de Filament 3. | Versiunea Filament 3 disponibila este blocata de Composer prin alerta de securitate. |
| 30.08.2026 | Decizie | Saptamana 16 este impartita 2 / 2 / 1: Congregația 1 primeste primele doua zile, Congregația 2 urmatoarele doua, iar Congregația 3 ultima zi. | Seeder-ul si autorizarea sunt implementate la nivelul fiecarei zile. |

## Verificari de lansare

- [ ] Productia foloseste HTTPS si variabile de mediu configurate.
- [ ] Backup-ul bazei de date este configurat si restaurarea a fost testata.
- [ ] Conturile si rolurile reale au fost validate.
- [ ] Linkurile publice nu afiseaza date personale sau alergii.
- [ ] Testele automate relevante trec.
- [ ] Toate echipele au parcurs fluxurile proprii de lucru.