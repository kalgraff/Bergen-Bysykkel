# Bergen Bysykkel WordPress Plugin

En WordPress-plugin som viser sanntidsinformasjon om tilgjengelighet av bysykler i Bergen. Pluginen viser antall ledige sykler og parkeringsplasser for stasjonene Nykirken og St. Jakobs Plass, med fargekodede statusindikatorer.

## Funksjoner

- Viser sanntidsdata fra den offisielle Bergen Bysykkel API
- Viser ledige sykler og parkeringsplasser for stasjonene Nykirken og St. Jakobs Plass
- Fargekodede statusindikatorer (grønn, oransje, rød) basert på tilgjengelighet
- Tilgjengelig både som widget og shortcode
- Responsivt design som fungerer på alle enheter
- All styling er inkludert i én fil for enkel tilpasning

## Installasjon

1. Last ned `bergen-bysykkel.zip` filen
2. Logg inn på WordPress-administrasjonspanelet
3. Gå til Utvidelser → Legg til ny → Last opp utvidelse
4. Last opp zip-filen og aktiver pluginen
5. Pluginen er nå klar til bruk

## Bruk

### Widget

1. Gå til Utseende → Widgeter
2. Dra "Bergen Bysykkel" widgeten til ønsket widget-område
3. Tilpass widget-tittelen om ønskelig
4. Lagre endringene

### Shortcode

Legg til følgende shortcode på enhver side eller innlegg:

```
[bergen_bysykkel]
```

## Tekniske detaljer

- Pluginen henter data fra Bergen Bysykkel GBFS API
- Data oppdateres hver gang siden lastes
- Fargeindikatorene endres basert på prosentvis tilgjengelighet:
  - Grønn: Mer enn 50% tilgjengelig
  - Oransje: Mellom 20% og 50% tilgjengelig
  - Rød: Mindre enn 20% tilgjengelig

## Systemkrav

- WordPress 5.0 eller høyere
- PHP 7.0 eller høyere

## Utvikler

Laget av Ove G. Kalgraff

## Lisens

Dette prosjektet er lisensiert under MIT-lisensen - se LICENSE-filen for detaljer.
