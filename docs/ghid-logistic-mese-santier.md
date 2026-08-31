# Ghid Logistic - Managementul Meselor pe Santier

> Totul facut in mod cuviincios si cu randuiala.

Acest ghid explica organizarea simpla a meselor pentru urmatoarele patru luni. Scopul este ca fiecare voluntar sa primeasca hrana calda, risipa de alimente sa fie evitata si alergiile sa fie gestionate responsabil.

## Echipa de constructii

- Introduceti numarul estimat de muncitori cu cel putin trei zile inainte de fiecare zi de lucru.
- In `Zile de masa`, deschideti ziua respectiva si modificati numai campul `Numar estimat persoane`.
- Actualizati imediat cifra daca este programata o echipa suplimentara, pentru ca bucataria sa poata ajusta cumparaturile.

## Coordonatorii de congregatii

- In `Congregatii`, deschideti congregatia proprie si alegeti `Retete aprobate pentru congregatie`. Acestea sunt retetele dintre care se poate alege pentru zilele voastre.
- La creare sau ulterior, completati `Nume asistent responsabil`, telefonul si emailul persoanei care organizeaza pregatirea meselor in congregatie.
- Nu este necesara introducerea numelor persoanelor. Comunicarea si organizarea interna raman la nivelul congregatiei.
- In `Calendar saptamanal`, alegeti butonul `Fisa [numele congregatiei]` pentru documentul personalizat cu zilele, preparatele, portiile, cumparaturile si datele asistentului.

## Echipa de bucatarie

- In `Bucatarie > Ingrediente`, introduceti pretul curent pentru fiecare produs o singura data. Nu modificati pretul separat in retete; orice actualizare se aplica automat la toate calculele.
- In `Bucatarie > Meniuri`, filtrati retetele dupa fel principal, ciorbe sau desert/gustare. Fiecare ingredient al retetei se alege din catalogul global, cu cantitatea per persoana.
- Folositi `Raport retete si costuri` din pagina Meniuri pentru a tipari costurile estimate per portie si pentru a identifica ingredientele fara pret.
- Cele 12 meniuri standard contin cantitatile per persoana; nu sunt necesare calcule manuale.
- In fiecare reteta, verificati si actualizati lista `Alergeni declarati` dupa ingredientele si etichetele folosite efectiv. Nu lasati o lista goala pana nu este verificata reteta.
- Pentru fiecare zi, alegeti felul principal aprobat pentru congregatia responsabila. Sistemul nu permite repetarea aceluiasi fel principal mai devreme de sase zile.
- Alegeti o singura `Ciorba saptamanii`, in una dintre cele cinci zile. Ciorba este suplimentara fata de felul principal.
- In pagina unei saptamani, folositi `Raport saptamanal` pentru lista de cumparaturi, programari si alergii.
- Daca o zi nu are meniu, raportul o va semnala. Completati meniul inainte de cumparaturi.

## Igiena si alergeni

- Fisa personalizata a congregatiei include alergenii declarati pentru fiecare fel si ghidul de igiena. Printati sau salvati fisa PDF si comunicati-o echipei care gateste.
- Orice persoana cu alergie sau intoleranta trebuie sa verifice alergenele declarate si sa anunte asistentul responsabil inainte de servire.
- Nu serviti un preparat daca ingredientele, alergenele sau riscul de contaminare incrucisata nu sunt clare. Pregatiti separat o portie potrivita, folosind ustensile si recipiente curate.
- Spalati mainile, separati carnea cruda de preparatele gata de servire, gatiti complet carnea si pastrati mancarea calda pana la livrare.

## Transport si distributie

- Portionati mancarea in caserole imediat dupa preparare si folositi cutii izoterme pentru transport.
- Folositi raportul saptamanal ca lista de verificare pentru preparatele si portiile fiecarui transport.

## Rapoarte si exporturi

- Administratorul poate folosi `Saptamani > Genereaza planificare` pentru un proiect nou: alege data de inceput, numarul de saptamani si cele trei congregatii in ordinea rotatiei. Sistemul creeaza cinci zile pe saptamana, imparte ultima saptamana 2/2/1 si blocheaza orice suprapunere cu zile existente.
- Generatorul creeaza doar structura de planificare. Completati apoi retetele, deserturile, portiile si bugetele pentru fiecare zi.
- Din pagina unei saptamani, administratorul foloseste `Creeaza link public`, alege congregatia si trimite linkul generat. Fisa publica arata numai zilele, preparatele, alergenii si lista de cumparaturi ale acelei congregatii; nu afiseaza contacte, bugete, costuri sau alte congregatii.
- `Saptamani`: deschideti saptamana dorita. Vedeti cele cinci zile si alegeti `Raport saptamanal`; pagina se poate printa sau salva ca PDF din browser.
- `Calendar saptamanal`: alegeti saptamana si vedeti toate zilele ca niste cartonase. Administratorul sau echipa de constructii poate actualiza rapid portiile direct din cartonas si confirma cu butonul bifat.
- Din Calendar, butonul `Fisa [numele congregatiei]` genereaza o pagina separata, gata de printare sau salvare PDF si trimitere catre acea congregatie.
- `Zile de masa`: filtrati dupa saptamana. Pentru fiecare zi completati congregatia responsabila, felul principal, ciorba daca este ziua aleasa si `Numar estimat persoane`.
- Selectati zilele filtrate si alegeti `Exporta zile selectate` pentru CSV sau XLSX, apoi trimiteti fisierul congregatiei responsabile.
- Pentru exporturi, coada Laravel trebuie sa fie pornita cu `php artisan queue:work` atunci cand nu rulati `composer dev`.