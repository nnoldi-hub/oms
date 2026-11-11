<x-filament-panels::page>
    <style>
        .guide-page { --guide-line: rgb(148 163 184 / .2); --guide-muted: rgb(100 116 139); }
        .guide-hero { align-items: end; background: linear-gradient(135deg, rgb(5 150 105 / .14), rgb(15 23 42 / .06)); border: 1px solid rgb(5 150 105 / .25); border-radius: 18px; display: flex; gap: 20px; justify-content: space-between; padding: 28px 30px; }
        .guide-hero h1 { font-size: clamp(1.8rem, 3vw, 2.5rem); letter-spacing: -.04em; }
        .guide-toc { display: grid; gap: 10px; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); }
        .guide-toc a { align-items: center; border: 1px solid var(--guide-line); border-radius: 10px; display: flex; font-size: .85rem; font-weight: 600; gap: 9px; padding: 10px 12px; text-decoration: none; }
        .guide-toc a:hover { border-color: rgb(5 150 105 / .45); background: rgb(5 150 105 / .06); }
        .guide-toc span { color: var(--guide-muted); font-weight: 700; }
        .guide-section { border: 1px solid var(--guide-line); border-radius: 14px; overflow: hidden; }
        .guide-section + .guide-section { margin-top: 12px; }
        .guide-section summary { align-items: center; cursor: pointer; display: flex; font-weight: 700; gap: 10px; list-style: none; padding: 14px 18px; }
        .guide-section summary::-webkit-details-marker { display: none; }
        .guide-section summary::before { color: var(--guide-muted); content: '\25B8'; font-size: .95rem; transition: transform .15s ease; }
        .guide-section[open] summary::before { transform: rotate(90deg); }
        .guide-section summary:hover { background: rgb(100 116 139 / .07); }
        .guide-section[open] summary { border-bottom: 1px solid var(--guide-line); }
        .guide-body { padding: 16px 18px; }
        .guide-body h4 { font-weight: 700; margin-top: 18px; }
        .guide-body h4:first-child { margin-top: 0; }
        .guide-body p, .guide-body li { font-size: .9rem; line-height: 1.6; }
        .guide-body ul, .guide-body ol { margin-top: 6px; padding-left: 22px; }
        .guide-body li + li { margin-top: 5px; }
        .guide-badge { border-radius: 999px; display: inline-flex; font-size: .68rem; font-weight: 700; letter-spacing: .04em; padding: 3px 8px; text-transform: uppercase; }
        .guide-badge--admin { background: rgb(244 63 94 / .12); color: rgb(190 18 60); }
        .guide-badge--coord { background: rgb(14 165 233 / .13); color: rgb(3 105 161); }
        .guide-badge--all { background: rgb(16 185 129 / .13); color: rgb(4 120 87); }
        .guide-code { background: rgb(15 23 42 / .07); border-radius: 6px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .82rem; padding: 2px 6px; }
        .guide-note { background: rgb(245 158 11 / .1); border-left: 3px solid rgb(245 158 11 / .65); border-radius: 0 8px 8px 0; font-size: .88rem; margin-top: 12px; padding: 10px 14px; }
        .guide-table { border-collapse: collapse; font-size: .88rem; margin-top: 6px; width: 100%; }
        .guide-table th, .guide-table td { border-top: 1px solid var(--guide-line); padding: 9px 12px; text-align: left; vertical-align: top; }
        .guide-table th { color: var(--guide-muted); font-size: .7rem; letter-spacing: .05em; text-transform: uppercase; }
        @media (max-width: 600px) { .guide-hero { align-items: start; flex-direction: column; padding: 22px; } }
    </style>

    <div class="guide-page space-y-6">
        <header class="guide-hero">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-success-600">❓ Consultare rapida</p>
                <h1 class="mt-2 font-bold">Ghid de utilizare</h1>
                <p class="mt-2 text-sm text-gray-500">
                    Cum schimbi data de inceput, cum se atribuie congregatiile, cum functioneaza
                    notele si avertismentele la meniuri si ce practici ajuta la folosirea eficienta a aplicatiei.
                </p>
            </div>
            <div class="hidden text-right text-sm text-gray-500 md:block">
                <span class="block font-semibold text-gray-700 dark:text-gray-200">Disponibil pentru</span>
                Toate rolurile
            </div>
        </header>

        <x-filament::section heading="Cuprins" description="Apasa pe un capitol pentru a-l deschide.">
            <nav class="guide-toc">
                <a href="#roluri"><span>1</span> Roluri si acces</a>
                <a href="#calendar"><span>2</span> Calendarul saptamanal</a>
                <a href="#data-inceput"><span>3</span> Data de inceput</a>
                <a href="#congregatii"><span>4</span> Congregatii si rotatie</a>
                <a href="#meniuri"><span>5</span> Meniuri si avertismente</a>
                <a href="#costuri"><span>6</span> Costuri, bugete, contributii</a>
                <a href="#rapoarte"><span>7</span> Rapoarte si exporturi</a>
                <a href="#comenzi"><span>8</span> Comenzi utile</a>
                <a href="#sfaturi"><span>9</span> Sfaturi de eficienta</a>
            </nav>
        </x-filament::section>

        <div>
            <details class="guide-section" id="roluri" open>
                <summary>1. Roluri si acces</summary>
                <div class="guide-body">
                    <table class="guide-table">
                        <thead>
                            <tr><th>Rol</th><th>Ce poate face in aplicatie</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><strong>Administrator</strong></td><td>Toate functiile: saptamani noi, generare program, mutari de zile/saptamani, congregatii, utilizatori, catalog de retete, rapoarte si comenzi.</td></tr>
                            <tr><td><strong>Coordonator congregatie</strong></td><td>Zilele congregatiei sale: meniuri, portii, buget, contributii, persoane, echipe si rapoarte. Nu poate muta zilele si nu poate edita alte congregatii.</td></tr>
                            <tr><td><strong>Echipa gastronomica</strong></td><td>Catalogul de retete si meniuri (feluri principale, ciorbe, deserturi) si costurile retetelor.</td></tr>
                            <tr><td><strong>Echipa constructii</strong></td><td>Actualizeaza numarul de persoane pe zilele existente; nu poate crea sau muta mese.</td></tr>
                            <tr><td><strong>Responsabil aprovizionare / congregatie, supraveghetor proiect</strong></td><td>Paginile de aprovizionare si rapoartele permise de rol.</td></tr>
                        </tbody>
                    </table>
                    <div class="guide-note">
                        Pagina principala dupa autentificare: <strong>Calendar saptamanal</strong>. Toate rolurile vad aceasta pagina,
                        dar pot modifica doar ce le permite rolul. <strong>Program general</strong> este o vedere de ansamblu, doar pentru citire.
                    </div>
                </div>
            </details>

            <details class="guide-section" id="calendar">
                <summary>2. Calendarul saptamanal - folosirea zilnica <span class="guide-badge guide-badge--all">Toate rolurile</span></summary>
                <div class="guide-body">
                    <h4>Alegerea saptamanii</h4>
                    <p>Selectorul de sus schimba saptamana afisata. Coordonatorii vad doar saptamanile care contin zile ale congregatiei lor; administratorul vede tot programul.</p>
                    <h4>Cardul unei zile</h4>
                    <ul>
                        <li><strong>Meniurile</strong>: alege felul principal, ciorba si desertul din liste derulante. Notele galbene apar imediat dupa selectie, inainte de salvare.</li>
                        <li><strong>Portii si buget</strong>: numarul estimat si confirmat de persoane, bugetul maxim al zilei si contributiile voluntarilor.</li>
                        <li><strong>Persoane si echipe</strong>: alocarea rolurilor pe zi (bucatar, ajutor etc.) si echipele de lucru, cu mesaje WhatsApp gata de trimis.</li>
                        <li><strong>S-a gatit</strong>: bifeaza dupa masa si poti indica felul real si costul real, ca rapoartele sa fie corecte.</li>
                    </ul>
                    <h4>Actiuni de administrator pe card</h4>
                    <ul>
                        <li><strong>Muta ziua</strong>: schimba data unei singure zile; meniurile si bugetele raman pe ziua respectiva.</li>
                        <li><strong>Muta saptamana</strong>: deplaseaza toate zilele si data de inceput cu acelasi numar de zile (intre -30 si +30).</li>
                        <li><strong>Zi libera</strong>: scoate ziua din program, cu optiunea de a muta restul zilelor saptamanii cu o zi.</li>
                        <li><strong>Zile lucratoare</strong>: bifele de sus adauga sau scot zile; scoaterea unei zile cu date cere confirmare.</li>
                    </ul>
                </div>
            </details>

            <details class="guide-section" id="data-inceput">
                <summary>3. Data de inceput a programului <span class="guide-badge guide-badge--admin">Administrator</span></summary>
                <div class="guide-body">
                    <p>
                        Nu exista o singura data de inceput globala: fiecare <strong>saptamana</strong> are propria data de inceput
                        (prima zi de masa). Poti schimba inceputul programului in trei feluri:
                    </p>
                    <h4>A. Program nou: "Genereaza planificare"</h4>
                    <ol>
                        <li>Deschide <span class="guide-code">Saptamani</span> (<span class="guide-code">/admin/weeks</span>) si apasa <strong>Genereaza planificare</strong>.</li>
                        <li>Seteaza <strong>data de inceput</strong> (o zi lucratoare; weekendul este refuzat daca saptamana nu include sambata), numarul de saptamani si cele trei congregatii in ordinea rotatiei.</li>
                        <li>Aplicatia creeaza saptamanile, mesele si aloca automat congregatiile. Numerotarea continua de la ultima saptamana, iar intervalul nu are voie sa se suprapuna cu mese existente.</li>
                    </ol>
                    <h4>B. Deplasarea intregului program</h4>
                    <p>
                        Daca vrei ca toate saptamanile sa incepa lunea, ruleaza intai previzualizarea, apoi comanda reala:
                    </p>
                    <p><span class="guide-code">php artisan oms:shift-weekdays --dry-run</span> apoi <span class="guide-code">php artisan oms:shift-weekdays</span></p>
                    <p>
                        Comanda muta in acelasi timp saptamanile, zilele de masa, planurile de aprovizionare si contributiile.
                        Refuza daca saptamanile nu au toate aceeasi zi de start si afiseaza avertismente pentru sarbatorile legale.
                        <span class="guide-code">--force</span> sare peste confirmarea finala.
                    </p>
                    <h4>C. O singura saptamana</h4>
                    <p>
                        Din calendar foloseste <strong>Muta saptamana</strong> (deplaseaza zilele si data de inceput impreuna)
                        sau editeaza data direct in <span class="guide-code">/admin/weeks</span>.
                    </p>
                    <div class="guide-note">
                        Ordinea recomandata: intai <span class="guide-code">--dry-run</span> ca sa vezi exact ce se muta, apoi rulezi comanda.
                        Dupa mutari, verifica notele galbene de la meniuri inainte de a salva din nou zilele.
                    </div>
                </div>
            </details>

            <details class="guide-section" id="congregatii">
                <summary>4. Congregatiile si rotatia <span class="guide-badge guide-badge--admin">Administrator</span></summary>
                <div class="guide-body">
                    <h4>Rotația automata la generare</h4>
                    <p>
                        La <strong>Genereaza planificare</strong>, congregațiile se rotesc in ordinea in care le selectezi:
                        saptamana 1 primeste prima congregatie, saptamana 2 a doua, saptamana 3 a treia, apoi se repeta.
                        <strong>Ultima saptamana</strong> se imparte automat <strong>2 zile / 2 zile / 1 zi</strong>, in aceasta ordine.
                    </p>
                    <h4>Reatribuire manuala dupa generare</h4>
                    <ul>
                        <li><strong>Saptamana noua</strong> (din calendar): alegi numarul, data si congregatia principala.</li>
                        <li><strong>Editare saptamana</strong> in <span class="guide-code">/admin/weeks</span>: schimbi "Congregatia principala".</li>
                        <li><strong>Per zi</strong> in <span class="guide-code">Zile de masa</span>: schimbi congregatia responsabila doar pentru o zi.</li>
                    </ul>
                    <p>
                        O zi noua adaugata cu bifa <strong>Zile lucratoare</strong> mosteneste congregatia saptamanii.
                        Nu exista re-rotatie automata a saptamanilor deja create: dupa generare, atribuirile se schimba manual.
                    </p>
                    <div class="guide-note">
                        Coordonatorii nu pot schimba congregatia unei zile; orice reatribuire se face de catre administrator.
                    </div>
                </div>
            </details>

            <details class="guide-section" id="meniuri">
                <summary>5. Meniuri: note de utilizare si avertismente <span class="guide-badge guide-badge--coord">Coordonatori</span></summary>
                <div class="guide-body">
                    <h4>Notele galbene (apar live, la selectie)</h4>
                    <p>Sub fiecare lista de meniuri pot aparea pana la trei note informative:</p>
                    <ul>
                        <li><strong>Gatit ultima data</strong> - data, acum cate zile si la ce congregatie a fost gatit felul (doar daca a fost folosit in ultimele 14 zile).</li>
                        <li><strong>Gatit de N ori in ultimele 6 saptamani</strong> - apare cand felul a fost gatit de cel putin 3 ori in aceasta perioada, in orice congregatie.</li>
                        <li><strong>Programat si pe...</strong> - urmatoarea zi din viitor in care meniul este deja programat, cu congregatia respectiva.</li>
                    </ul>
                    <p>Notele folosesc toate congregatiile, ignora zilele libere si nu se pun niciodata pe ziua pe care o editezi acum. Ele <strong>nu blocheaza salvarea</strong>.</p>
                    <h4>Avertismentele de varietate (galbene, nu blocheaza)</h4>
                    <ul>
                        <li>Felul principal a mai fost folosit in ultimele 10 zile (se recomanda minimum 10 zile intre doua preparari).</li>
                        <li>Ciorba este recomandata la a doua si a patra masa a saptamanii.</li>
                        <li>O saptamana poate avea maximum 2 ciorbe.</li>
                        <li>Aceeasi ciorba de doua ori in aceeasi saptamana sau in ciorba imediat anterioara.</li>
                        <li>Felul principal si ciorba folosesc acelasi ingredient dominant.</li>
                    </ul>
                    <h4>Ce blocheaza salvarea</h4>
                    <p>Doar problemele reale de date: o reteta <strong>inactiva</strong> sau un <strong>tip gresit</strong> (de exemplu o ciorba aleasa ca fel principal).</p>
                    <h4>Umplere automata a meniurilor</h4>
                    <p>
                        <span class="guide-code">php artisan oms:fill-menus --dry-run</span> arata ce s-ar completa, iar
                        <span class="guide-code">php artisan oms:fill-menus</span> completeaza doar zilele ramase in format draft,
                        respectand ordinea meselor si regula celor doua ciorbe.
                    </p>
                </div>
            </details>

            <details class="guide-section" id="costuri">
                <summary>6. Costuri, bugete si contributii <span class="guide-badge guide-badge--coord">Coordonatori</span></summary>
                <div class="guide-body">
                    <ul>
                        <li><strong>Cost estimat</strong>: calculat din retete, ingrediente, preturile din catalog si numarul de portii.</li>
                        <li><strong>Cost real</strong>: se trece cand bifezi <strong>S-a gatit</strong>, impreuna cu felul real, daca a diferit.</li>
                        <li><strong>Buget maxim</strong>: pe fiecare zi; pagina <strong>Costuri si buget</strong> compara estimatul cu realul pe saptamani.</li>
                        <li><strong>Contributii</strong>: se inregistreaza pe zi sau pe congregatie si intra in fondul congregatiei.</li>
                    </ul>
                    <p>
                        Administratorul poate rula periodic <span class="guide-code">php artisan oms:snapshot-closed-meals</span>
                        ca sa inghete costurile zilelor trecute sau gatite; astfel modificarile ulterioare de pret nu schimba rapoartele vechi.
                    </p>
                    <div class="guide-note">
                        Coordonatorii vad doar costurile, bugetele si contributiile congregației lor. Pagina <strong>Program general</strong> nu afiseaza deloc aceste date.
                    </div>
                </div>
            </details>

            <details class="guide-section" id="rapoarte">
                <summary>7. Rapoarte si exporturi</summary>
                <div class="guide-body">
                    <ul>
                        <li><strong>Program general</strong>: toate zilele tuturor congregațiilor, doar pentru citire (data, congregatia, meniurile, s-a gatit, felul real).</li>
                        <li><strong>Costuri si buget</strong>: comparatia estimat/real pe saptamani, cu marcarea saptamanilor incheiate.</li>
                        <li><strong>Raport saptamanal PDF</strong>: zile, roluri, alergeni, cantitati si costuri; optiunea de a ascunde numerele de telefon.</li>
                        <li><strong>Fisa de pregatire</strong> a unei zile: portii, retete, lista de cumparaturi, alergeni.</li>
                        <li><strong>Rapoarte de aprovizionare</strong>: necesarul agregat si listele de cumparaturi.</li>
                        <li><strong>Linkuri publice</strong>: pagina publica a congregației si pagina publica a unei zile, pe baza unui token, fara date personale.</li>
                        <li><strong>Maine la masa</strong>: pe dashboard, cu portii, alergeni si lista de cumparaturi.</li>
                    </ul>
                </div>
            </details>

            <details class="guide-section" id="comenzi">
                <summary>8. Comenzi utile <span class="guide-badge guide-badge--admin">Administrator</span></summary>
                <div class="guide-body">
                    <p>Se ruleaza din terminal, in folderul aplicatiei:</p>
                    <table class="guide-table">
                        <thead>
                            <tr><th>Comanda</th><th>Rol</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><span class="guide-code">php artisan oms:shift-weekdays</span></td><td>Muta tot programul ca fiecare saptamana sa incepa lunea. Optiuni: <span class="guide-code">--dry-run</span>, <span class="guide-code">--force</span>.</td></tr>
                            <tr><td><span class="guide-code">php artisan oms:fill-menus</span></td><td>Completeaza meniurile lipsa doar in zilele draft. Optiune: <span class="guide-code">--dry-run</span>.</td></tr>
                            <tr><td><span class="guide-code">php artisan oms:snapshot-closed-meals</span></td><td>Ingheata costul zilelor trecute sau gatite.</td></tr>
                            <tr><td><span class="guide-code">php artisan oms:import-recipes</span></td><td>Actualizeaza retetele, ingredientele si preturile din catalogul global.</td></tr>
                            <tr><td><span class="guide-code">php artisan oms:import-menu-catalog</span></td><td>Importa catalogul OMS fara a modifica programarile existente.</td></tr>
                            <tr><td><span class="guide-code">php artisan oms:fix-split-ingredients</span></td><td>Marcheaza "de configurat" componentele rezultate din impartirea ingredientelor combinate.</td></tr>
                            <tr><td><span class="guide-code">php artisan oms:reset-user-password</span></td><td>Reseteaza parola unui utilizator printr-un prompt securizat.</td></tr>
                        </tbody>
                    </table>
                </div>
            </details>

            <details class="guide-section" id="sfaturi">
                <summary>9. Sfaturi pentru folosire eficienta</summary>
                <div class="guide-body">
                    <ol>
                        <li><strong>Salveaza dupa fiecare modificare</strong> - notele si avertismentele sunt preventive si nu blocheaza; verificarea finala ramane la tine.</li>
                        <li><strong>Citeste notele galbene inainte de a alege</strong> - iti arata daca felul a fost gatit recent, des sau urmeaza sa fie gatit la alta congregatie.</li>
                        <li><strong>Pastreaza ciorbele la a doua si a patra masa</strong> si maximum doua pe saptamana.</li>
                        <li><strong>Bifeaza "S-a gatit" imediat dupa masa</strong>, cu felul si costul real - altfel rapoartele si statisticile rama in urma.</li>
                        <li><strong>Foloseste Program general</strong> ca sa vezi ce gatesc celelalte congregatii si sa eviti repetitiile la nivel de program.</li>
                        <li><strong>Inainte de mutari mari</strong> (saptamani, tot programul) ruleaza comenzile cu <span class="guide-code">--dry-run</span> si verifica rezultatul.</li>
                        <li><strong>Ruleaza periodic <span class="guide-code">oms:snapshot-closed-meals</span></strong> ca sa pastrezi costurile istorice neschimbate.</li>
                    </ol>
                </div>
            </details>
        </div>
    </div>
</x-filament-panels::page>
