<style>
    /* Explicit content sizes include the borders in the finished physical dimensions. */
    .id-card { position: relative; width: 85.20mm; height: 53.58mm; border: 0.2mm solid #333; box-sizing: content-box; overflow: hidden; background: #fff; color: #111; font-family: DejaVu Sans, Arial, sans-serif; font-size: 5.5pt; line-height: 1.15; text-align: left; }
    .id-card * { box-sizing: border-box; }
    .id-card p, .id-card h2 { margin: 0; padding: 0; }
    .id-label { font-size: 5pt; font-weight: normal; line-height: 1.1; }
    .id-seal { position: absolute; top: 5mm; left: 2.5mm; width: 16mm; height: 16mm; object-fit: contain; }
    .id-heading { position: absolute; top: 6.5mm; left: 19.5mm; width: 36.8mm; text-align: center; }
    .id-republic, .id-office { font-family: DejaVu Serif, Georgia, serif; font-size: 6.3pt; font-style: italic; line-height: 1.15; }
    .id-office { margin-top: 1.2mm !important; font-size: 5.7pt; }
    .id-heading .municipality { margin-top: 1.8mm; font-size: 5.8pt; font-weight: bold; letter-spacing: -0.1pt; line-height: 1.15; }
    .id-photo { position: absolute; top: 3mm; right: 2.5mm; width: 25.1mm; height: 25.1mm; border: 0.15mm solid #555; box-sizing: content-box; overflow: hidden; color: #666; text-align: center; font-size: 5.4pt; line-height: 1.4; }
    .id-photo img { display: block; width: 100%; height: 100%; object-fit: cover; object-position: center; }
    .id-photo span { position: absolute; top: 8mm; left: 0; width: 100%; }
    .id-photo small { font-size: 4.4pt; }
    .id-number { position: absolute; top: 0.6mm; right: 2.5mm; width: 32mm; font-size: 4.6pt; line-height: 1; text-align: right; }
    .id-number strong { margin-left: 1mm; font-size: 5.1pt; }
    .id-personal { position: absolute; top: 28.8mm; left: 2.5mm; width: 80.2mm; }
    .id-personal .id-label { position: absolute; top: 0.7mm; left: 0; }
    .id-personal .name { margin-left: 9mm; padding-bottom: 0.3mm; border-bottom: 0.15mm solid #777; font-size: 7.2pt; font-weight: bold; line-height: 1.05; text-transform: uppercase; overflow-wrap: anywhere; word-wrap: break-word; }
    .id-personal .medium-name { font-size: 5.2pt; line-height: 1; }
    .id-personal .compact-name { font-size: 4.2pt; }
    .id-personal .long-name { font-size: 3.5pt; }
    .id-address { position: absolute; top: 33.2mm; left: 2.5mm; width: 80.2mm; }
    .id-address .id-label { position: absolute; top: 0.2mm; left: 0; }
    .id-address .address { margin-left: 12mm; padding-bottom: 0.3mm; border-bottom: 0.15mm solid #777; font-size: 5.5pt; line-height: 1.05; overflow-wrap: anywhere; word-wrap: break-word; }
    .id-address .compact-address { font-size: 4.3pt; line-height: 1; }
    .id-contact { position: absolute; top: 38mm; right: 2.5mm; width: 40mm; font-size: 4.8pt; line-height: 1; }
    .id-contact-label { position: absolute; top: 0.1mm; left: 0; width: 13mm; }
    .id-contact-value { display: block; margin-left: 13mm; padding-bottom: 0.1mm; border-bottom: 0.15mm solid #777; font-weight: normal; text-align: center; }
    .id-dates { position: absolute; top: 40.8mm; left: 2.5mm; width: 80.2mm; height: 5.8mm; text-align: center; }
    .id-dates .id-label { padding-top: 0.35mm; font-size: 4.4pt; font-style: italic; line-height: 1; }
    .id-date-value { border-bottom: 0.15mm solid #777; padding-bottom: 0.25mm !important; font-size: 6.8pt; font-weight: bold; line-height: 1; white-space: nowrap; }
    .id-birth { position: absolute; top: 0; left: 0; width: 26mm; }
    .id-age { position: absolute; top: 0; left: 28mm; width: 8mm; }
    .id-sex { position: absolute; top: 0; left: 38mm; width: 14mm; }
    .id-issuance { position: absolute; top: 0; left: 54mm; width: 26.2mm; }
    .id-validity { position: absolute; top: 46.8mm; left: 2.5mm; width: 80.2mm; font-size: 3.8pt; line-height: 1; text-align: center; }
    .id-thumbprint { position: absolute; top: 49.8mm; left: 12mm; width: 61.2mm; height: 2mm; border-bottom: 0.15mm solid #555; text-align: center; }
    .id-holder-name { font-size: 4.4pt; line-height: 1; text-transform: uppercase; overflow-wrap: anywhere; word-wrap: break-word; }
    .id-holder-name.compact-name { font-size: 3.2pt; }
    .id-holder-name.long-name { font-size: 2.4pt; }
    .id-thumbprint-label { position: absolute; top: 2.2mm; left: 0; width: 100%; font-size: 3.3pt; font-style: italic; line-height: 1; }
    .id-back-heading { position: absolute; top: 2.5mm; left: 2.5mm; width: 80.2mm; }
    .id-card .id-back-heading h2 { color: #111; font-size: 6.1pt; font-style: italic; font-weight: bold; line-height: 1.1; text-align: center; }
    .id-benefits { position: absolute; top: 6.5mm; left: 2.5mm; width: 80.2mm; }
    .id-benefits ul { margin: 0; padding: 0 0 0 2.4mm; list-style-type: disc; font-size: 4.5pt; line-height: 1.1; }
    .id-benefits li { margin: 0; padding: 0; }
    .id-workspace-preview .id-benefits ul { line-height: 1.35; }
    .id-warning { position: absolute; top: 31mm; left: 2.5mm; width: 56mm; font-size: 4.1pt; font-style: italic; font-weight: bold; line-height: 1.15; text-align: center; }
    .id-officials { position: absolute; top: 40.1mm; left: 2.5mm; width: 56mm; height: 11mm; text-align: center; }
    .id-official { position: absolute; top: 0; left: 0; width: 27mm; }
    .id-official + .id-official { left: 29mm; }
    .id-officials img { height: 4.8mm; max-width: 24mm; object-fit: contain; }
    .id-officials .signature-space { height: 4.8mm; }
    .id-officials .official-name { padding-top: 0.7mm; border-top: 0.15mm solid #555; font-size: 4.3pt; font-weight: bold; line-height: 1.2; text-transform: uppercase; overflow-wrap: anywhere; word-wrap: break-word; }
    .id-officials .official-role { padding-top: 0.5mm; font-family: DejaVu Serif, Georgia, serif; font-size: 4.2pt; }
    .id-qr { position: absolute; top: 29.9mm; right: 2.5mm; width: 21mm; height: 21mm; }
    .id-qr-placeholder { border: 0.15mm dashed #777; padding-top: 7mm; color: #666; font-size: 5pt; text-align: center; }
    .id-qr-label { position: absolute; top: 51.2mm; right: 2.5mm; width: 21mm; font-size: 3.2pt; line-height: 1; text-align: center; }
    .id-draft { position: absolute; top: 23mm; left: 16mm; width: 40mm; color: #a12626; font-size: 5pt; font-weight: bold; text-align: center; }
</style>
