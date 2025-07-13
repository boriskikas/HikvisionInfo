# Hikvision Device Info & Camera Viewer

Ovaj projekt pruža jednostavno web sučelje za dohvaćanje detaljnih informacija s Hikvision uređaja (poput DVR-ova, NVR-ova i IP kamera) koristeći njihov ISAPI protokol.

## Sadržaj

- [Značajke](#značajke)
- [Preduvjeti](#preduvjeti)
- [Instalacija i postavljanje](#instalacija-i-postavljanje)
- [Korištenje](#korištenje)
- [Struktura projekta](#struktura-projekta)
- [Licenca](#licenca)

## Značajke

-   **Informacije o uređaju:** Dohvaća i prikazuje osnovne podatke o uređaju (naziv, model, firmware, serijski broj, MAC adresa).
-   **Mrežne postavke:** Prikazuje detaljne mrežne konfiguracije (IP adresa, maska podmreže, pristupnik, DNS, MAC adresa, UPnP, Zeroconf).
-   **Detaljni ISAPI Endpointi:** Omogućuje pregled podataka s raznih ISAPI endpointa (konfiguracija kanala, status snimanja, pohrana, korisnički računi, itd.), prikazane kao strukturirani podaci i sirovi XML.
-   **Pregled kamera uživo:** Prikazuje snimke uživo s otkrivenih kamera putem `camera_proxy.php` skripte.
-   **Modalni prikaz slike:** Klikom na sliku kamere otvara se modalni prozor s punom rezolucijom slike.
-   **Preuzimanje snimke:** Mogućnost preuzimanja trenutne snimke kamere.
-   **Osvježavanje slike kamere:** Gumb za ručno osvježavanje slike kamere.
-   **Moderan UI:** Intuitivno korisničko sučelje s Bootstrapom 5 i prilagođenim CSS-om.
-   **Tamni mod:** Opcija za prebacivanje na tamnu temu za ugodniji rad u uvjetima slabog osvjetljenja.
-   **Responzivan dizajn:** Prilagodljiv prikaz na različitim veličinama ekrana (desktop, tablet, mobilni).
-   **Loading animacije:** Vizualna povratna informacija tijekom učitavanja podataka.

## Preduvjeti

Da biste pokrenuli ovu aplikaciju, trebat će vam:

-   **PHP** (verzija 7.4 ili novija preporučena) s omogućenim `cURL` i `SimpleXML` ekstenzijama.
-   **Web server** (poput Apache ili Nginx).
-   **Hikvision uređaj** (DVR, NVR, IP kamera) s omogućenim ISAPI protokolom i poznatim IP-om, portom, korisničkim imenom i lozinkom.

## Instalacija i postavljanje

1.  **Klonirajte repozitorij:**
    ```bash
    git clone [https://github.com/boriskikas/HikvisionInfo.git](https://github.com/boriskikas/HikvisionInfo.git)
    cd hikvision-info-viewer
    ```
    Ili jednostavno preuzmite ZIP arhivu projekta.

2.  **Postavite datoteke:**
    Raspakirajte ili kopirajte datoteke `indexcam.php`, `camera_proxy.php` i `stil.css` (ako postoji i nije inline) u korijenski direktorij vašeg web servera ili u poddirektorij (npr. `/var/www/html/hikvision-app/`).

3.  **Provjera dozvola za `snapshots/` direktorij:**
    Aplikacija pokušava stvoriti `snapshots/` direktorij u istom direktoriju kao i `camera_proxy.php` za privremeno spremanje slika. Osigurajte da vaš web server ima prava pisanja u taj direktorij (npr. `chmod -R 777 snapshots/` ili preporučeno `chmod -R 755 snapshots/` i `chown -R www-data:www-data snapshots/`). Ako direktorij ne može biti kreiran ili nije zapisljiv, funkcionalnost spremanja snimki neće raditi, ali će se slike i dalje posluživati direktno.

4.  **Provjerite PHP ekstenzije:**
    Osigurajte da su `php-curl` i `php-xml` (za SimpleXML) ekstenzije omogućene u vašoj PHP konfiguraciji.

## Korištenje

1.  Otvorite aplikaciju u svom web pregledniku:
    `http://vas_server_ip_ili_domena/index.php`
    (ili `http://vas_server_ip_ili_domena/vas_pod_direktorij/index.php` ako ste je smjestili u poddirektorij).

2.  Unesite sljedeće podatke u obrazac:
    -   **IP Adresa:** IP adresa vašeg Hikvision uređaja.
    -   **Port:** Port za pristup uređaju (npr. `80`, `81`, `443` ako je HTTPS).
    -   **Korisničko ime:** Korisničko ime za pristup uređaju (npr. `admin`).
    -   **Lozinka:** Lozinka za pristup uređaju.

3.  Kliknite gumb **"Pretraži"**.

4.  Nakon učitavanja, moći ćete vidjeti:
    -   Osnovne informacije o uređaju.
    -   Pregled kamera uživo. Kliknite na sliku kamere za otvaranje u velikom modalnom prozoru.
    -   Preuzmite sliku s kamere klikom na gumb "Preuzmi sliku".
    -   Razne ISAPI endpointe s detaljnim podacima.

5.  Koristite gumb **<i class="bi bi-moon-fill"></i>** u donjem desnom kutu za prebacivanje između svijetle i tamne teme.

## Struktura projekta

-   `index.php`: Glavna PHP datoteka koja sadrži HTML strukturu, PHP logiku za dohvaćanje podataka i prikaz, te sav CSS i JavaScript.
-   `camera_proxy.php`: Pomoćna PHP skripta koja služi kao proxy za dohvaćanje slika s kamera i njihovo prikazivanje. Također može privremeno spremiti snimke.
-   `stil.css`: (Opcionalno, ako se izdvoji iz `indexcam.php`) Dodatni CSS stilovi.
-   `snapshots/`: Direktorij u koji `camera_proxy.php` može spremati privremene snimke.

## Licenca

Ovaj projekt je otvorenog koda i dostupan pod [MIT licencom](https://opensource.org/licenses/MIT).
