<style>
    /* Content dimensions plus two 0.2 mm borders match the approved outer card size. */
    .id-card { position: relative; width: 85.20mm; height: 53.58mm; border: 0.2mm solid #344054; box-sizing: content-box; color: #111; background: #fff; overflow: hidden; font-family: DejaVu Sans, Arial, sans-serif; }
    .id-card * { box-sizing: border-box; }
    .id-card p { margin: 0; }
    .id-seal { position: absolute; top: 3mm; left: 3mm; width: 12mm; height: 12mm; object-fit: contain; }
    .id-heading { position: absolute; top: 3mm; left: 16mm; width: 40mm; text-align: center; font-size: 6.2pt; line-height: 1.3; }
    .id-heading .municipality { margin-top: 1mm; font-size: 7pt; font-weight: bold; }
    .id-heading .validity { margin-top: 0.5mm; font-size: 3.8pt; line-height: 1.1; }
    .id-number { position: absolute; right: 3mm; top: 3mm; width: 26mm; font-size: 6.5pt; text-align: right; }
    .id-photo { position: absolute; top: 12mm; right: 3mm; width: 25.4mm; height: 25.4mm; object-fit: cover; }
    .id-personal { position: absolute; top: 17mm; left: 3mm; width: 51mm; font-size: 6.2pt; line-height: 1.3; }
    .id-personal .name { min-height: 5mm; font-weight: bold; }
    .id-personal .address { margin-top: 1mm; height: 10mm; font-size: 5.7pt; }
    .id-personal .contact { margin-top: 0.5mm; }
    .id-dates { position: absolute; top: 38.5mm; left: 3mm; width: 79mm; font-size: 5.6pt; text-align: center; border-collapse: collapse; table-layout: fixed; }
    .id-dates td { padding: 0 0.5mm; vertical-align: top; }
    .id-dates .value { border-bottom: 0.15mm solid #777; }
    .id-signing { position: absolute; top: 44mm; left: 3mm; width: 79mm; text-align: center; font-size: 5.5pt; }
    .id-signing img { height: 3.5mm; max-width: 45mm; object-fit: contain; }
    .id-signing .signature-space { height: 3.5mm; }
    .id-signing .printed-name { border-bottom: 0.15mm solid #777; font-size: 5.5pt; }
    .id-benefits { position: absolute; top: 2mm; left: 3mm; width: 79mm; }
    .id-benefits h2 { margin: 0 0 1mm; font-size: 6.3pt; text-align: center; }
    .id-benefits ul { margin: 0; padding-left: 2.5mm; font-size: 4.5pt; line-height: 1.1; }
    .id-warning { position: absolute; top: 29.5mm; left: 3mm; width: 48mm; font-size: 4.7pt; line-height: 1.1; }
    .id-qr { position: absolute; top: 29.5mm; right: 3mm; width: 22mm; height: 22mm; }
    .id-qr-label { position: absolute; top: 51.5mm; right: 3mm; width: 22mm; font-size: 3.8pt; text-align: center; }
    .id-officials { position: absolute; top: 39.5mm; left: 3mm; width: 51mm; table-layout: fixed; border-collapse: collapse; text-align: center; font-size: 4.7pt; }
    .id-officials td { width: 50%; padding: 0 1mm; vertical-align: bottom; }
    .id-officials img { height: 5mm; max-width: 23mm; object-fit: contain; }
    .id-officials .signature-space { height: 5mm; }
    .id-officials .official-name { border-bottom: 0.15mm solid #777; font-size: 4.6pt; overflow-wrap: anywhere; }
    .id-draft { position: absolute; bottom: 0; right: 0; font-size: 5pt; background: #fff; padding: 0.5mm; color: #9b1c1c; }
</style>
