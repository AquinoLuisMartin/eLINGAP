<style>
    /* Explicit content sizes include the borders in the finished physical dimensions. */
    .id-card { position: relative; width: 85.20mm; height: 53.58mm; border: 0.2mm solid #111; box-sizing: content-box; overflow: hidden; background: #fff; color: #111; font-family: DejaVu Sans, Arial, sans-serif; font-size: 5.5pt; line-height: 1.15; text-align: left; }
    .id-card * { box-sizing: border-box; }
    .id-card p, .id-card h2 { margin: 0; padding: 0; }
    .id-label { font-size: 5.8pt; font-weight: normal; line-height: 1.05; }
    .id-seal { position: absolute; top: 3.1mm; left: 2.2mm; width: 17.5mm; height: 17.5mm; object-fit: contain; }
    .id-heading { position: absolute; top: 4.5mm; left: 20.4mm; width: 36.2mm; text-align: center; }
    .id-republic, .id-office { font-family: DejaVu Serif, Georgia, serif; font-size: 6.7pt; font-style: italic; line-height: 1.15; }
    .id-office { margin-top: 1.4mm !important; font-size: 6.3pt; }
    .id-heading .municipality { margin-top: 1.6mm; font-size: 5.9pt; font-weight: bold; letter-spacing: 0; line-height: 1.1; }
    /* The photo opening is one inch square, with its border outside that area. */
    .id-photo { position: absolute; top: 2.75mm; right: 2.2mm; width: 25.4mm; height: 25.4mm; border: 0.15mm solid #222; box-sizing: content-box; overflow: hidden; color: #555; text-align: center; font-size: 5.4pt; line-height: 1.35; }
    .id-photo img { display: block; width: 100%; height: 100%; object-fit: cover; object-position: center; }
    .id-photo span { display: block; width: 100%; padding-top: 8.2mm; }
    .id-photo small { font-size: 4.5pt; }
    .id-number { position: absolute; top: 0.35mm; right: 2.2mm; width: 34mm; height: 2.2mm; font-size: 5pt; line-height: 1; text-align: right; overflow: hidden; }
    .id-number strong { margin-left: 0.8mm; font-size: 5.4pt; }
    .id-personal { position: absolute; top: 28.6mm; left: 2.4mm; width: 80.4mm; }
    .id-personal .id-label { position: absolute; top: 0.55mm; left: 0; width: 8.8mm; }
    .id-personal .name { margin-left: 9mm; padding-bottom: 0.2mm; border-bottom: 0.25mm solid #111; font-size: 7.8pt; font-weight: bold; line-height: 1.02; text-transform: uppercase; overflow-wrap: anywhere; word-wrap: break-word; }
    .id-address { position: absolute; top: 32.8mm; left: 2.4mm; width: 80.4mm; height: 6.9mm; }
    .id-address .id-label { position: absolute; top: 0.2mm; left: 0; width: 11.8mm; }
    .id-address .address { margin-left: 12mm; padding-bottom: 0.2mm; border-bottom: 0.25mm solid #111; font-size: 6.2pt; font-weight: bold; line-height: 1.05; overflow-wrap: anywhere; word-wrap: break-word; }
    .id-contact { position: absolute; top: 39.8mm; left: 2.4mm; width: 35mm; font-size: 5.5pt; line-height: 1; }
    .id-contact-label { position: absolute; top: 0.05mm; left: 0; width: 13.4mm; }
    .id-contact-value { display: block; margin-left: 13.7mm; padding-bottom: 0; border-bottom: 0.2mm solid #111; font-weight: normal; line-height: 1; text-align: left; }
    .id-dates { position: absolute; top: 42.65mm; left: 2.4mm; width: 80.4mm; height: 4.5mm; text-align: center; }
    .id-dates .id-label { padding-top: 0; font-size: 3.7pt; font-style: italic; line-height: 1; }
    .id-date-value { border-bottom: 0.25mm solid #111; padding-bottom: 0.1mm !important; font-size: 6pt; font-weight: bold; line-height: 1; white-space: nowrap; }
    .id-birth { position: absolute; top: 0; left: 0; width: 22.8mm; }
    .id-age { position: absolute; top: 0; left: 25.8mm; width: 10.6mm; }
    .id-sex { position: absolute; top: 0; left: 39.4mm; width: 15mm; }
    .id-issuance { position: absolute; top: 0; left: 57.4mm; width: 23mm; }
    .id-validity { position: absolute; top: 47.4mm; left: 2.4mm; width: 80.4mm; font-size: 4pt; line-height: 1; text-align: center; }
    .id-thumbprint { position: absolute; top: 49.25mm; left: 2.4mm; width: 80.4mm; height: 2.65mm; border-bottom: 0.25mm solid #111; text-align: center; }
    .id-holder-name { font-size: 6pt; font-weight: bold; line-height: 1; text-transform: uppercase; white-space: nowrap; }
    .id-thumbprint-label { position: absolute; top: 2.75mm; left: 0; width: 100%; font-size: 3.2pt; font-style: italic; line-height: 1; }
    .id-back-heading { position: absolute; top: 2.8mm; left: 2.4mm; width: 80.4mm; }
    .id-card .id-back-heading h2 { color: #111; font-size: 6.6pt; font-style: italic; font-weight: bold; line-height: 1.15; text-align: center; }
    .id-benefits { position: absolute; top: 6.5mm; left: 2.4mm; width: 80.4mm; }
    .id-benefits ul { margin: 0; padding: 0 0 0 2.6mm; list-style-type: disc; font-size: 4.65pt; line-height: 1.13; }
    .id-benefits li { margin: 0; padding: 0; }
    .id-warning { position: absolute; top: 31mm; left: 2.4mm; width: 58.6mm; font-size: 4.35pt; font-style: italic; font-weight: bold; line-height: 1.15; text-align: center; }
    .id-officials { position: absolute; top: 40.2mm; left: 2.4mm; width: 58.6mm; height: 11.2mm; text-align: center; }
    .id-official { position: absolute; top: 0; left: 0; width: 28mm; }
    .id-official + .id-official { left: 30.6mm; }
    .id-officials img { height: 5.2mm; max-width: 25mm; object-fit: contain; }
    .id-officials .signature-space { height: 5.2mm; }
    .id-officials .official-name { padding-top: 0.45mm; border-top: 0.25mm solid #111; font-size: 5.2pt; font-weight: bold; line-height: 1.15; text-transform: uppercase; overflow-wrap: anywhere; word-wrap: break-word; }
    .id-officials .official-role { padding-top: 0.3mm; font-size: 4.3pt; }
    .id-qr { position: absolute; top: 31.2mm; right: 2.2mm; width: 19.2mm; height: 19.2mm; }
    .id-qr-placeholder { border: 0.2mm dashed #777; padding-top: 6.4mm; color: #666; font-size: 5pt; text-align: center; }
    .id-qr-label { position: absolute; top: 50.8mm; right: 2.2mm; width: 19.2mm; font-size: 3.3pt; line-height: 1.1; text-align: center; }
    .id-draft { position: absolute; top: 23mm; left: 16mm; width: 40mm; color: #a12626; font-size: 5pt; font-weight: bold; text-align: center; }
</style>
